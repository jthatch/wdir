<?php $offline = false;
/**
 * wdir.php
 *  (Web Directory)
 *
 *  This program provides a nice looking directory listing. It is a drop in replacement for both apache and nginx
 *  webservers, as they both have pretty basic directory listings.
 *
 *  Fancy URL's: /directory/file.txt vs basic URL'S: wdir.php?fnode=/directory/file.txt can be achieved by configuring
 *  nginx with the below config and then enabling fancyUrl's in the $opts
 *
 * User: jthatch https://github.com/jthatch
 * Date: 11/08/16
 */


$wdir = new Wdir;


// Okay run if we are okay
$wdir->isOkay() ?
    $wdir->run() :
    $wdir->showErrors();


/**
 * Class Translate
 */
class Wdir
{
    private static $offline = false;
    // the file system node we are attempting to access. can be a directory or a file
    private static $basePath = false;
    private static $fnode = false;
    private static $request = false;
    private static $commandLine = false;
    private static $startTime;
    private static $logBuffer = [];
    private static $scriptName = 'wdir.php'; // replaced in __construct with script name
    private static $scriptPath = ''; // stores the web path, set in __construct  

    private static $db = false; // only set if we have $opts['db']

    private static $opts = [
        'fancyUrls' => false,
        'directFile' => true,
        'logOutput' => true,
        'hideSelf' => true,
        'theme' => 'light',
        'db' => "sqlite:wdir.sqlite",
        'hidden' => [
            'wdir.php',
            'wdir.sqlite',
            'wdir.sqlite-journal',
            'favicon.ico'
        ]
    ];

    private static $logStatus = [
        'success' => 0,
        'error' => 0,
        'info' => 0,

    ];

    /**
     * setup some basic stuff
     */
    function __construct()
    {
        self::$startTime = microtime(true);
        error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
        set_time_limit(0);        // ensure PHP is not running in Safe Mode otherwise this has no effect
        ini_set('max_execution_time', 0); //300 seconds = 5 minutes
        ini_set('memory_limit', '368M');

        global $argv;            // need to declare this global when accessing it via a class
        global $offline;

        self::$offline = $offline;

        self::$scriptName = basename(__FILE__) == 'index.php' ? '' : basename(__FILE__);
        self::$scriptPath = str_replace(basename(__FILE__), '', filter_input(INPUT_SERVER, 'PHP_SELF'));

        // are we running via command line or web?
        self::$commandLine = (php_sapi_name() == 'cli' || empty($_SERVER['REMOTE_ADDR']));
        if (self::$commandLine) {
            self::$basePath = isset($argv) && isset($argv[1]) ? $argv[1] : getcwd();
            self::$fnode = isset($argv) && isset($argv[2]) ? $argv[2] : self::$basePath;
        }
        else {
            self::$basePath = getcwd();
            if (isset($_GET['fnode'])) {
                $fnode = filter_input(INPUT_GET, 'fnode');
                self::$request = $fnode;
                self::$fnode = self::$basePath . $fnode;
            }
            else
                self::$fnode = getcwd();
        }

        /**
         * handle database if it's present
         * optional: sqlite3 wdir.sqlite
         * then enter:
         * create table hits (path text, file text, hits integer, updated_at integer, primary key(path, file));
         * .exit
         */
        if (self::opt('db')) {
            self::$opts['db'] = str_replace("{basePath}", self::$basePath, self::$opts['db']);
            self::$db = self::dbSetup();
        }
    }

    /**
     * Setup the db
     * @return bool|PDO
     */
    private static function dbSetup()
    {
        try {
            $db = new PDO(self::opt('db'));
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $db;
        }
        catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Register a hit on a file, either by inserting the record or updating the existing
     * @param $path
     * @param $file
     * @return string
     */
    private static function dbHit($path, $file)
    {
        $updated_at = (new \DateTime())->getTimestamp();
        $sql = "INSERT OR REPLACE INTO hits VALUES (:path, :file, COALESCE( (SELECT hits FROM hits WHERE path=:path AND file=:file), 0) + 1,:updated_at);";
        $query = self::$db->prepare($sql);

        $query->bindParam(":path", $path);
        $query->bindParam(":file", $file);
        $query->bindParam(":updated_at", $updated_at);
        try {
            $query->execute();
            return "added hit";
        }
        catch (\PDOException $e) {
            return $e->getMessage();
        }
    }

    private static function dbGet($path = '/')
    {
        if (!self::$db)
            return [];

        // fix path
        $path = str_replace(self::$basePath, "", $path);
        if (strlen($path) < 1)
            $path = '/';
        else {
            $path = str_replace("\\", "/", trim($path));
            $path = (substr($path, -1) != '/') ? $path .= '/' : $path;
        }

        $sql = "SELECT file, hits, updated_at from hits where path = :path";
        $query = self::$db->prepare($sql);

        $query->bindParam(":path", $path);
        try {
            $query->execute();
            $result = $query->fetchAll();
            foreach($result as $row) {
                $hits[ $row['file'] ] = [$row['hits'], $row['updated_at']];
            }
            return $hits;

        }
        catch (PdoExeception $e) {
            return [];
        }
    }

    /**
     * Retrieve an option
     * @param $item
     * @return mixed|bool
     */
    private static function opt($item)
    {
        if (isset(self::$opts[$item]))
            return self::$opts[$item];

        return false;
    }


    /**
     * if the site has been set offline and the user isn't an admin
     */
    private static function isOffline()
    {
        return (self::$offline);
    }

    public function run()
    {
        // get our current fnode. This allows us to determine if we are serving up a file or a directory
        $fnode = self::fNode();

        /**
         * note when serving files, if self::$opts->directFile is true, then we bypass below and serve the file directly
         */
        if ( isset( $_GET['hit'] )) {
            if (stristr($_GET['hit'], '::')) {
                list($file, $path) = explode("::", $_GET['hit']);
                if ($file && $path) {
                    echo self::dbHit($path, $file);
                    exit;
                }
            }
            header("HTTP/1.1 400 Bad Request");
            exit;
        }

        else if (self::isOffline()) {
            die("offline");
        }

        else if ($fnode->isFile) {
            self::log("Serving file: ". $fnode->path);
            self::download($fnode);
        }

        else if ($fnode->isDir) {
            self::log("Reading directory of ". $fnode->real);
            if ( isset( $_COOKIE['sort'] ) && stristr($_COOKIE['sort'], ':') !== false ) {
                list($key, $desc) = explode(":", $_COOKIE['sort'], 2);
            }
            else {
                list($key, $desc) = ['mage', true];
            }
            $fnode->sort = (object) ['key' => $key, 'desc' => $desc];

            $fnode->files = self::order(
                $fnode->files,
                $fnode->sort->key,
                $fnode->sort->desc);

            self::show($fnode);
        }
    }

    /**
     * FileSystem Node. Can be a file or a directory. Basically represents what we are looking at
     *
     * @return stdClass
     */
    private static function fNode()
    {
        $fnode = new stdClass;

        if ( is_file( self::$fnode) ) {
            $fnode->name = basename(self::$fnode);
            $fnode->isFile = true;
            $fnode->isDir = !$fnode->isFile;
			$fnode->isRoot = false;

            $fnode->path = dirname(self::$fnode);
            $fnode->size = filesize(self::$fnode);
            $fnode->nize = self::human_filesize($fnode->size);
            $fnode->mage = filemtime(self::$fnode);
            $fnode->ago = self::time2str($fnode->mage);

            $fnode->real = "{$fnode->path}/{$fnode->name}";
            $fnode->url = self::url($fnode);

        }
        elseif ( is_dir( self::$fnode ) ) {

            $hits = self::dbGet(self::$fnode);


            $fnode->isDir = true;
            $fnode->isFile = !$fnode->isDir;
			$fnode->isRoot = false;
            $fnode->files = [];
            $fnode->real = self::$fnode;
            $fnode->path = dirname(self::$fnode);
            $fnode->url = self::url($fnode);

            foreach( new \DirectoryIterator(self::$fnode) as $file) {

                // handle parent directory if we are allowed to go up
                if ($file->isDot() && $file->getFilename() == '..' && self::allowedDir($file->getRealPath())) {
                    $node = (object) [
                        'name' => $file->getFilename() . " (Up a directory)",

                        'isDir' => $file->isDir(),
                        'isFile' => $file->isFile(),

                        'path' => $file->getRealPath(),
                        'real' => $file->getRealPath(),
                        'size' => $file->getSize(),
                        'nize' => self::human_filesize( $file->getSize() ),
                        'mage' => $file->getMTime(),
                        'ago' => self::time2str($file->getMTime()),
                    ];
                    $node->url = self::url($node);
                    $file = self::url($node, true);
                    $path = str_replace($node->name, '', self::url($node, true));
                    $node->webpath = $file . "::" . $path;
                    $node->hits = isset( $hits[ $file ] ) ? $hits[ $file ][0] : '-';
                    $node->last_hit = isset( $hits[ $file ] ) ? self::time2str($hits[ $file ][1]) : '-';
                }
                else if (!$file->isDot() &&
                    $file->getFilename()[0] != '.') {
                    if (self::opt('hideSelf') && $file->getFilename() == basename(__FILE__)) {
						$fnode->isRoot = true;
                        continue;
					}
                    if (in_array($file->getFilename(), (array) self::opt('hidden')))
                        continue;
                    $node = (object)[
                        'name' => ($file->isDir() ? '/' : '') . $file->getFilename(),

                        'isDir' => $file->isDir(),
                        'isFile' => $file->isFile(),

                        'path' => $file->getPath(),
                        'real' => $file->getRealPath(),
                        'size' => $file->getSize(),
                        'nize' => $file->isDir() ? '-' : self::human_filesize($file->getSize()),
                        'mage' => $file->getMTime(),
                        'ago' => self::time2str($file->getMTime())
                    ];
                    $node->url = self::url($node);
                    $file = self::url($node, true);
                    $path = str_replace($node->name, '', self::url($node, true));
                    $node->webpath = $file . "::" . $path;
                    $node->hits = isset( $hits[ $file ] ) ? $hits[ $file ][0] : '-';
                    $node->last_hit = isset( $hits[ $file ] ) ? self::time2str($hits[ $file ][1]) : '-';
                }
                else {continue;}

                $fnode->files[] = $node;


            }

			if ($fnode->isRoot) {
            	$fnode->latest = self::latest($fnode->files);
			}
        }
        else {
            self::log("Unable to open " . self::$fnode, "error");
        }

        return $fnode;
    }

    /**
     * Serves a file for download
     * @param $fnode
     */
    private static function download($fnode = object )
    {
        if (!self::$commandLine) {
            header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

            header("Content-Description: File Transfer");

            header("Content-Type: application/save");

            header("Content-Length: " . $fnode->size);
            header("Content-Disposition: attachment; filename=" . $fnode->name);
            header("Content-Transfer-Encoding: binary");
        }

        readfile("{$fnode->path}/{$fnode->name}");
    }

    /**
     * If possible return the most recently modified file as an html string
     * @param array $files
     * @return bool|string
     */
    private static function latest($files = [])
    {
        usort($files, function($a, $b) {
            return $a->mage < $b->mage;
        });
        return isset($files[0]) ? $files[0] : false;
    }

    /**
     * If possible return the most recently modified file as an html string
     * @param array $files
     * @param string $key
     * @param bool $desc
     * @return bool|string
     */
    private static function order($files = [], $key = 'mage', $desc = false)
    {
        if (!in_array($key, ['mage', 'name', 'size', 'hits']))
            $key = 'name';

        usort($files, function($a, $b) use ($key, $desc)  {
                return $desc ? ($a->{$key} < $b->{$key}) : ($a->{$key} > $b->{$key});
        });
        return $files;
    }

    /**
     * Display directory listing, either over command line or via web
     * @param $fnode
     */
    private static function show($fnode = object)
    {
        if (self::$commandLine) {
            printf("%s %s %s\n",
                str_pad('NAME', 36, ' ', STR_PAD_RIGHT),
                str_pad('SIZE', 6, ' ', STR_PAD_RIGHT),
                str_pad('AGE', 15, ' ', STR_PAD_RIGHT)
            );
            echo str_repeat("-", 60) . "\n";
            foreach($fnode->files as $file) {
                printf("%s %s %s\n",
                    str_pad($file->name, 36, ' ', STR_PAD_RIGHT),
                    str_pad($file->nize, 6, ' ', STR_PAD_RIGHT),
                    str_pad($file->ago, 15, ' ', STR_PAD_RIGHT)
                );
            }
        }
        elseif (self::opt('theme') == 'light') {
            $scriptName = self::$scriptName;
			$homeUrl = self::$scriptPath . self::$scriptName;
			$title = self::$scriptPath;
			$titlePath = str_replace($scriptName . "?fnode=/", "", $fnode->url);
			if ($titlePath && !$fnode->isRoot)
				$title .= $titlePath;
            $latest =  (isset($fnode->latest) && $fnode->latest) ? "Latest: <a href='{$fnode->latest->url}' data-webpath='{$fnode->latest->webpath}'>{$fnode->latest->name}</a> <em>(added " . strtolower($fnode->latest->ago) . ")</em>" : '';
            $total = sizeof($fnode->files);
            $s = $total == 1 ? '' : 's';
            exec("df -H -P", $output);
            /*
            [0] => Filesystem      Size  Used Avail Use% Mounted on
            [1] => udev             13G  4.1k   13G   1% /dev
            [2] => tmpfs           2.6G  1.5M  2.6G   1% /run
            */
            if (isset($output[1])) {
                $split = preg_split('/\s+/', $output[1]);
                $disc = [
                    'disk'      => $split[0],
                    'mount'     => $split[5],
                    'mb_total'  => $split[1],
                    'mb_used'   => $split[2],
                    'mb_free'   => $split[3],
                    'percent'   => $split[4],
                ];
            }
            $total_size = 'N/A';
            $total_free = "N/A";
            $total_percent = "N/A";
            if (isset($disc)) {
                $total_size = $disc['mb_total'];
                $total_free = $disc['mb_free'];
                $total_percent = $disc['percent'];
            }
            $sort_name = $fnode->sort->key == 'name' ? 'on' : '';
            $sort_hits = $fnode->sort->key == 'hits' ? 'on' : '';
            $sort_size = $fnode->sort->key == 'size' ? 'on' : '';
            $sort_added = $fnode->sort->key == 'mage' ? 'on' : '';

            $html = <<<EOF
    <html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>$title - wdir</title>
        <style type="text/css">
         body {font-family: arial,sans-serif;font-size:14px;line-height:22px;background:#eee;color:#777;font-size:14px;line-height:140%}
         a:link {color:#FF4136;border-bottom:1px dotted #004B91;text-decoration:none;line-height:1.4;font-size:1.1em;}
         a:hover {color:red;background:#ddd}
         a:visited {color:#A52A2A}

         header {width:96%;position:relative;margin:0 auto 20px auto;}
         header .l {width:70%;padding:5px 0;float:left;}
         header .r {width:30%;padding:5px 0;float:right;clear:right;}
         .imgholder {width:100%;}
         .imgholder img, .imgholder .logo {width:100%;text-align:right;opacity:1;font-size:18px;}
         .stats {text-align:right}
         * {word-break:break-all}
         h1 {width:100%;line-height:90%;font-size:30px;color:#111;word-break:break-all;margin:0;padding:20px 2px 10px 0}
         h1 small {display:block;text-align:left;clear:both;color:#999;font-size:18px;}
         table {min-width:80%;width:96%;max-width:100%;margin:auto;text-align:left;}
         table th {text-align:left;color:#000;background:#ddd;cursor:pointer;text-transform:uppercase;padding:4px 3px;}
         table tr:hover {background:#ddd;}
         table td {word-break:break-all; padding: 1px 3px;}

         table th, table td {text-align:right;}
         table th:first-child, table td:first-child {text-align:left}

         table th:hover, table th.on {background:#bbb;color:#000}
         table tr th:first-child {width:70%;}
         #main {position:relative;z-index:2;}
         #logoholder {position:absolute;top:0;right:2%;height:65px;width:30%;max-width:400px;}
	.del {font-size:12px !important;color:#999 !important;margin-left:10px;border:0;text-decoration:none;}
	@media (max-width:360px) {
	    header {width:96%;position:relative;margin:0 auto 20px auto;display:table}
            header .l {width:100%;padding:5px 0;float:none;display:table-footer-group}
            header .r {width:100%;padding:5px 0;float:none;display:table-header-group}
            .imgholder {width:100%;}
            .imgholder img {width:100%;text-align:right;opacity:1;/*max-height:75px;*/}
            .stats {text-align:center}
	    table {width:100% !important;min-width:100% !important;}

	}
	</style>
        <script>
        function sortTable(table, col, reverse) {
            var tb = table.tBodies[0], // use `<tbody>` to ignore `<thead>` and `<tfoot>` rows
            tr = Array.prototype.slice.call(tb.rows, 0), // put rows into array
            i;
            console.log('reverse before: ' + reverse);
            reverse = -((+reverse) || -1);
            console.log(reverse);
            tr = tr.sort(function(a, b) {
                if (a.cells[col].getAttribute('data-sort')) {
                    var aa = parseInt(a.cells[col].getAttribute('data-sort'), 10);
                    var bb = parseInt(b.cells[col].getAttribute('data-sort'), 10);
                    if (aa > bb)
                        return reverse * 1;
                    else if (aa === bb)
                        return reverse * 0;
                    else
                        return reverse * -1;
                }

                return reverse // `-1 *` if want opposite order

                * (a.cells[col].textContent.trim()// using `.textContent.trim()` for test
                .localeCompare(b.cells[col].textContent.trim())
                );
            });
            for (i = 0; i < tr.length; ++i)
                tb.appendChild(tr[i]);
            // append each row in order
        }
        function makeSortable(table) {
            var th = table.tHead, i;
            th && (th = th.rows[0]) && (th = th.cells);
            if (th)
                i = th.length;
            else
                return;
            // if no `<thead>` then do nothing
            while (--i >= 0)
                (function(i) {
                    var dir = 0;
                    th[i].addEventListener('click', function() {
                    var sort = th[i].getAttribute('data-sort');
                    document.cookie = 'sort='+ sort + ':' + (!dir ^ 0) + '; expires=Fri, 3 Aug 2031 20:47:11 UTC; path=/';

                    for (var key in th) {
                        th[key].className = '';
                    }
                    th[i].classList.add('on');
                        sortTable(table, i, (dir = 1 - dir))
                    });
                }(i));
        }
        function makeAllSortable(parent) {
            parent = parent || document.body;
            var t = parent.getElementsByTagName('table')
              , i = t.length;
            while (--i >= 0)
                makeSortable(t[i]);
        }
        function addHits(parent) {
            parent = parent || document.body;
            var a = parent.getElementsByTagName('a'),
            i = a.length;

            while (--i >= 0) {
                if (a[i].getAttribute('data-webpath')) {
                    a[i].addEventListener('click', function(event) {
                        var href = this.getAttribute('href');
                        var webpath = this.getAttribute('data-webpath');

                        event.preventDefault();
                        window.dl = false;

                        var r = new XMLHttpRequest();
                        r.open("GET", "/{$scriptName}?hit=" + webpath + '&cb=' + (+ (new Date)), true);
                        r.onload = function(){
                            window.location = href;
                            window.dl = true;
                            if (r.status === 200)
                                console.log(r.responseText);
                            else
                                console.log('error');
                        };
                        r.send();
                        // backup if page doesnt load in 2 secs
                        setTimeout(function(){if (!window.dl)window.location = href;}, 2e3);
                    });
                }
				// del
				if (a[i].getAttribute('data-del')) {
					a[i].addEventListener('click', function(event) {
						var del = this.getAttribute('data-del'),
							clicked = this.getAttribute('data-clicked'),
							el = this;

						event.preventDefault();
						if (clicked) {
							var r = new XMLHttpRequest();
                        	r.open("GET", "/$scriptName?del=" + del + '&cb=' + (+ (new Date)), true);
                        	r.onload = function(){
                            	if (r.status === 200) {
                                	eval(r.responseText);
									el.innerHTML = 'done';
								}
                            	else
                                	alert("ERROR: Unable to delete " + del);
                        	};
                        	r.send();
						}
						else {
							this.setAttribute('data-clicked', true);
							this.innerHTML = 'confirm';
							setTimeout(function(){
								el.innerHTML = 'del';
								el.removeAttribute('data-clicked');
							}, 3e3, el);
						}
					});
				}
            }
        }
        window.onload = function() {
            makeAllSortable();
            addHits();
        }
        </script>
    </head>
    <body>
    <div id="bg"></div>
    <div id="main">
        <header>
            <div class="l">
                <h1>Index of $title <small>$total item$s</small></h1>
                $latest
            </div>
            <div class="r">
                <div class="imgholder">
                    <a href="$homeUrl"><div class="logo">[wdir]</div></a>
                </div>
                <div class="stats">
                    <small>
                        Total size: <strong>$total_size</strong> <span style="color:#aaa">&nbsp;|&nbsp;</span>
                        Free: <strong>$total_free</strong> <span style="color:#aaa">&nbsp;|&nbsp;</span>
                        Disc Usage: <strong>$total_percent</strong>
                    </small>
                </div>
            </div>
            <div style="clear:both"></div>
        </header>
        <table>
            <thead>
                <tr>
                    <th class="$sort_name" data-sort="name">Name</th>
                    <th class="$sort_hits" data-sort="hits">Hits</th>
                    <th class="$sort_size" data-sort="size">Size</th>
                    <th class="$sort_added" data-sort="mage">Added</th>
                </tr>
            </thead>
            <tbody>
EOF;
            foreach ($fnode->files as $fnode) {
                $html .= "<tr>";
                $html .=    "<td><a href='{$fnode->url}' data-webpath='{$fnode->webpath}'>{$fnode->name}</a></td>";
                $html .=    "<td data-sort=" . intval($fnode->hits) . " title='Last Access: ". $fnode->last_hit . "'>". $fnode->hits ."</td>";
                $html .=    "<td data-sort=" . $fnode->size . ">". $fnode->nize ."</td>";
                $html .=    "<td data-sort=" . $fnode->mage . ">". str_replace(" ", '&nbsp',$fnode->ago) . "</td>";
                $html .= "</tr>";
            }
            $html .= "</tbody></table></body></html>";
            echo $html;
        }
    }

    /**
     * Takes a string an converts variable templates {{likethis}} into code
     * @param string $input
     * @param array $data
     * @return mixed|string
     */
    private static function view($input = '', $data = [])
    {
        foreach($data as $name => $value) {
            $input = str_replace('{{'.$name.'}}', $value, $input);
        }
        return $input;
    }

    /**
     * Generate a nice URL
     * @param $fnode object
     * @param $fancyUrlsForceDisabled bool
     * @return string
     */
    private static function url($fnode = object, $fancyUrlsForceDisabled = false) {
        $path = self::$basePath;
        $fullpath = $fnode->real;
        $fancyUrls = !$fancyUrlsForceDisabled ? self::opt('fancyUrls') : true;

        if ($path === $fullpath) {
            return self::$scriptPath . self::$scriptName;
		}
            //return (self::opt('fancyUrls') ? '' : "wdir.php?fnode=") . "/";

        // make sure our path has a trailing slash
        $path = str_replace("\\", "/", trim($path));
        $path = (substr($path, -1) != '/') ? $path .= '/' : $path;
        if ($fnode->isDir)
            $fullpath = (substr($fullpath, -1) != '/') ? $fullpath .= '/' : $fullpath;

        if (self::opt('directFile') && $fnode->isFile) {
            return self::$scriptPath . str_replace($path, '', $fullpath);
        }
        return ($fancyUrls ? '' : self::$scriptName . "?fnode=") . "/" . str_replace($path, '', $fullpath);
    }

    /**
     * ensure a directory is allowed within our path
     * @param $real
     * @return bool
     */
    private static function allowedDir($real = object)
    {
        $path = self::$basePath;

        $path = (substr($path, -1) != '/') ? $path .= '/' : $path;
        $real = (substr($real, -1) != '/') ? $real .= '/' : $real;

        $path = explode("/", $path);
        $real = explode("/", $real);

        $allowed = true;
        if (sizeof($real) < sizeof($path))
            $allowed = false;
        else {
            array_walk($real, function (&$dir, $key) {
                if (!isset($path[$key]) || $dir !== $path[$key])
                    $allowed = false;
            });
        }

        return $allowed;
    }

    public static function deleteFiles($path) {
        if (is_file($path)) {
                unlink($path);
            }
            else {
                $dir = new DirectoryIterator($path);
                foreach ($dir as $fileinfo) {
                    if ($fileinfo->isFile() || $fileinfo->isLink()) {
                        unlink($fileinfo->getPathName());
                    } elseif (!$fileinfo->isDot() && $fileinfo->isDir()) {
                        self::deleteFiles($fileinfo->getPathName());
                    }
                }
                rmdir($path);
            }
        }

    /**
     * Check if our target directory exists and is readable and we are running from command line
     * @return bool
     */
    public function isOkay()
    {
        if (self::$fnode &&
            file_exists(self::$fnode) &&
            self::$basePath &&
            is_dir(self::$basePath)
            &&
            self::allowedDir( self::$fnode )
        ) {
            self::log("Legit file or directory " . self::$basePath . " as working directory, accessing fnode: ". self::$fnode);
            // prevent people trying to access /etc/passwd or other sensitive locations
            self::$fnode = self::secureFnode(self::$fnode);
            return true;
        }
        else if (! self::allowedDir( self::$fnode )) {
            self::log("Error: " . self::$fnode ." is invalid", 'error');
            return false;
        }
        /*else if ( !is_dir(self::$basePath) ) {
            self::log("Error: " . self::$basePath . " is not a valid directory", 'error');
            return false;
        }*/
        else if (!file_exists(self::$fnode)) {
            self::log("Error: " . self::$request . " is not a valid directory or file", 'error');
            return false;
        }
        else {
            self::log("Error: " . self::$fnode . " is invalid", 'error');
            return false;
        }
    }

    /**
     * Log messages to the screen
     * @param string $message message
     * @param string $type error|info|success
     * @return void
     */
    private static function log($message = null, $type = 'info')
    {
        if ($type !== 'error')
            return false;
        $line = self::executionTime() . ' ';
        switch ($type) {
            case 'error':
                self::$logStatus['error']++;
                $line .= "[!]\t";
                break;
            case 'info':
                self::$logStatus['info']++;
                $line .= "[>]\t";
                break;
            case 'success':
                self::$logStatus['success']++;
                $line .= "[+]\t";
        }
        $line .= $message . PHP_EOL;
        array_push(self::$logBuffer, $line);
        if ( self::$commandLine )
            echo $line;
    }

    /**
     * little function to test how long script execution took
     */
    private static function executionTime()
    {
        return (self::$startTime) ? sprintf("%.4f", microtime(true) - self::$startTime) : 0.0000;
    }

    /**
     * Prevents filesystem attack vectors like these:
     *  http://www.ush.it/2009/02/08/php-filesystem-attack-vectors/
     *  http://www.ush.it/2009/07/26/php-filesystem-attack-vectors-take-two/
     *  https://en.wikipedia.org/wiki/Directory_traversal_attack
     * @param string $fnode
     * @return string
     */
    private static function secureFnode($fnode = '')
    {

        return realpath($fnode);
    }

    /**
     * Show some basic help
     * @return bool
     */
    public function showErrors()
    {
        if ( ! self::$opts['logOutput'])
            return false;
        if ( !self::$commandLine ) {
            //echo "<br>not command line<br>";
            //echo "<br>log buffer<br>";
            //print_r(self::$logBuffer);
            echo "<pre>";
            echo implode("\n", self::$logBuffer);
            echo "</pre>";
        }
        else {
            echo PHP_EOL .
                str_repeat("-", 80) . PHP_EOL .
                '  Usage: ' .
                PHP_EOL . PHP_EOL .
                '    php ' . basename(__FILE__) . ' [BASE DIRECTORY] [TARGET FILE]' .
                PHP_EOL . PHP_EOL .
                '    Example: php ' . basename(__FILE__) . ' /home/user/ /home/user/pics/image.jpg' .
                PHP_EOL .
                str_repeat("-", 80) .
                PHP_EOL;
        }
    }

    /**
     * Convert bytes into a nice filesize
     * @param $bytes
     * @param int $decimals
     * @return string
     */
    private static function human_filesize($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        //$sz = 'bkmgtp';
        $factor = floor((strlen($bytes) - 1) / 3);
        $decimals = $factor < 2 ? 0 : 2;
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    /**
     * return a nice string
     * @param $ts
     * @return bool|string
     */
    private static function time2str($ts)
    {
        if(!ctype_digit($ts)) {
            $ts = strtotime($ts);
        }
        $diff = time() - $ts;
        if($diff == 0) {
            return 'now';
        } elseif($diff > 0) {
            $day_diff = floor($diff / 86400);
            if($day_diff == 0) {
                if($diff < 60) return 'just now';
                if($diff < 120) return '1 minute ago';
                if($diff < 3600) return floor($diff / 60) . ' minutes ago';
                if($diff < 7200) return 'an hour ago';
                if($diff < 86400) return floor($diff / 3600) . ' hours ago';
            }
            if($day_diff == 1) { return 'Yesterday'; }
            if($day_diff < 7) { return $day_diff . ' days ago'; }
            if($day_diff < 31) { return ceil($day_diff / 7) . ' weeks ago'; }
            if($day_diff < 60) { return 'last month'; }
            return date('F Y', $ts);
        } else {
            $diff = abs($diff);
            $day_diff = floor($diff / 86400);
            if($day_diff == 0) {
                if($diff < 120) { return 'in a minute'; }
                if($diff < 3600) { return 'in ' . floor($diff / 60) . ' minutes'; }
                if($diff < 7200) { return 'in an hour'; }
                if($diff < 86400) { return 'in ' . floor($diff / 3600) . ' hours'; }
            }
            if($day_diff == 1) { return 'Tomorrow'; }
            if($day_diff < 4) { return date('l', $ts); }
            if($day_diff < 7 + (7 - date('w'))) { return 'next week'; }
            if(ceil($day_diff / 7) < 4) { return 'in ' . ceil($day_diff / 7) . ' weeks'; }
            if(date('n', $ts) == date('n') + 1) { return 'next month'; }
            return date('F Y', $ts);
        }
    }
}
