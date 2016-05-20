<?php
//New Relic Manual install script 
//Walden Bodtker
//9_11_2014
// v1.0.1

echo "<head><style>code {margin:18px;color:red;font-weight:bolder;font-size:1.1em;}</style></head>";


  ob_start();
  phpinfo();
  $phpinfo = array('phpinfo' => array());
  if(preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>'
    .'(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s',
    ob_get_clean(), $matches, PREG_SET_ORDER)) {
    foreach($matches as $match) 
      if(strlen($match[1])) {
        $phpinfo[$match[1]] = array();
      } elseif(isset($match[3])) {
        $phpinfo[end(array_keys($phpinfo))][$match[2]] = isset($match[4]) ? array($match[3], $match[4]) : $match[3];
      } else {
        $phpinfo[end(array_keys($phpinfo))][] = $match[2];
      };
  };
  
  $tmp = $phpinfo['phpinfo']['Thread Safety'];
  $inidir = addcslashes(rtrim($phpinfo['phpinfo']['Scan this dir for additional .ini files'], "/"), " ");
  $MODULEDIR = addcslashes(rtrim(ini_get('extension_dir'), "/"), " ");
  $architecture = (PHP_INT_SIZE * 8);
  $PHPAPI = $phpinfo['phpinfo']['PHP Extension'];
  $template = './scripts/newrelic.ini.template';
  $URL = 'https://download.newrelic.com/php_agent/release/';
  $PHPZTS = null;
  $ARCH = null;
  $OS = PHP_OS; //Thanks Mike LaSpina
  $perl = shell_exec('which perl'); 
  $paste = 'perl -pi';
  $inst = 'install';
  

  switch($OS){
    case 'Linux':
      $tar = 'linux';
      break;
    case 'Darwin':
      $tar = 'osx';
      $ARCH = 'x86_64';
      break;
    case'SunOS':  //for solaris need to move from /usr/bin to /opt/local (ask Mike)
      $tar = 'solaris';
      break;
   /* case 'FreeBSD':   //FreeBSD is deprecated commenting out all FreeBSD code as could not creat e functional install in FreeBSD 9.2 whether using newrelic-install or manual
      $tar = 'freebsd';
      break;*/
    default:
      exit("<p>Sorry this environment may not be supported. Please read our <a href='https://docs.newrelic.com/docs/"
           ."agents/php-agent/getting-started/new-relic-php#requirements'>compatibility and requirements</a>. If you "
           ."feel you received this message in error please open a ticket at <a href='https://support.newrelic.com'>"
           ."https://support.newrelic.com</a> and send us the following line </br></br><strong> ".php_uname()." </strong>"
           ."</br></br>and a link to or copy of your <a href='https://docs.newrelic.com/docs/agents/php-agent/"
           ."troubleshooting/using-phpinfo-verify-php'>phpinfo()</a> as html</p>"); 
  };

  if ($tmp !== 'disabled'){
    $PHPZTS = '-zts';
  };

  if ($architecture !== 64){
    $ARCH = 'x86';
  };

  if ($tar === 'osx'){
      $ARCH = 'x86_64';
  };

  if ($PHPAPI == 20050922){
    if ($tar === 'osx'){
      $ARCH = 'universal';
    }
    $URL = 'https://download.newrelic.com/php_agent/archive/4.4.5.35/';
    $phpini = $phpinfo['phpinfo']['Loaded Configuration File'];
  } else {
    $phpini = php_ini_loaded_file();
  };



  /*if ($tar === 'freebsd'){
    $URL = 'https://download.newrelic.com/php_agent/archive/4.5.5.38/';
  };*/

  if (extension_loaded('newrelic')){
    $inst = 'upgrade';
  echo "<h1>Congratulations!!</h1></br>\n<h3>You have successfully installed New Relic to your system</h3></br>\n<h3>"
      ."Please visit <a href='https://newrelic.com'>newrelic.com</a> and within a few minutes you should see your application reporting</h3>";
  echo "If you are still not seeing application data, please open a ticket at <a href='https://support.newrelic.com'>"
      ."https://support.newrelic.com</a> and attach a copy of your logs from <code>/var/log/newrelic</code> and "
      ."a link to or copy of your <a href='https://docs.newrelic.com/docs/agents/php-agent/troubleshooting/using-"
      ."phpinfo-verify-php'>phpinfo()</a> as html</p>";
  echo "You can use this script again to upgrade the PHP agent any time a new version is released.";
};
  
  
  echo "<h3>Enter the following commands into your terminal as root to manually $inst the New Relic PHP Agent</h3>"
      ."</br>\nTo become root:   <code>su</code>   or   <code>sudo su</code></br>\n</br>\n";
  echo "<code>".htmlspecialchars('wget -r -l1 -nd -A '. escapeshellarg("$tar.tar.gz") ." ". escapeshellarg($URL)).'</code></br>';
  echo "<code>".htmlspecialchars("gzip -dc newrelic*.tar.gz | tar xf -")."</code></br>\n"; //extract to a named location datestamped
  echo "<code>".htmlspecialchars("mv newrelic*.tar.gz old_newrelic.tar.gz")."</code></br>\n";
  echo "<code>".htmlspecialchars("cd newrelic-php5*")."</code></br>\n"; // use absolute directory name
  echo "<code>".htmlspecialchars("cp -f ". escapeshellarg("./agent/$ARCH/newrelic-$PHPAPI$PHPZTS.so"). " ".  escapeshellarg("$MODULEDIR/newrelic.so"))."</code></br>\n";
  echo "<code>".htmlspecialchars("cp -f ". escapeshellarg("./daemon/newrelic-daemon.$ARCH") ." /usr/bin/newrelic-daemon")."</code></br>\n";

  if (!extension_loaded('newrelic')){
  echo "<code>".htmlspecialchars("mkdir /var/log/newrelic")."</code></br>\n";
  echo "<code>".htmlspecialchars("chmod 755 /var/log/newrelic")."</code></br>\n";

  if (strlen($perl) < 1){
    $paste = 'sed -i';
  };
  
  echo "<h3>Set your license key and application name. Be sure to replace yourLicenseKey and yourApplicationName with "
      ."your real license key and the app name you desire.</h3></br>\n";
  echo "<code style=\"margin-right:0px;\">".htmlspecialchars(escapeshellarg($paste)." -e 's/\"REPLACE_WITH_REAL_KEY\"/")."</code><strong style=\"color:blue;\">"
      ."yourLicenseKey</strong><code style=\"margin-left:0px;\">".htmlspecialchars("/g' ".escapeshellarg($template))."</code></br>\n";
  echo "<code style=\"margin-right:0px;\">".htmlspecialchars(escapeshellarg($paste)." -e 's/PHP Application/")."</code><strong style=\"color:blue;\">"
      ."yourApplicationName</strong><code style=\"margin-left:0px;\">".htmlspecialchars("/g' ".escapeshellarg($template))."</code></br>\n";
     
  if ($inidir !== '(none)' && strlen($inidir) > 0){
    echo "<code>".htmlspecialchars("cp ".escapeshellarg($template)." ".escapeshellarg($inidir)."/newrelic.ini")."</code></br>\n</br>\n"; 
  } else {
    $phpini = rtrim(rtrim($phpini, '/'), '/php.ini').'/php.ini';
    echo "<code>".htmlspecialchars("cat ".escapeshellarg($template)." >> ".escapeshellarg($phpini))."</code></br>\n</br>\n";
  };
  };
   echo "<code>".htmlspecialchars("/usr/bin/newrelic-daemon")."</code></br>\n";

  echo "<h3>Restart your webserver and reload this page. If New Relic is loaded you should see a congratulatory message.</h3>";

 //potentially echo values of variables in the script for supportability 
  if (!extension_loaded('newrelic')){
  echo "<p>Variables for troubleshooting</p>";
  // echo getenv('PATH');
  // echo exec('which perl');

  echo "<ul><li>PHPZTS:$PHPZTS</li><li>ARCH: $ARCH</li><li>URL: $URL</li><li>"
      ."OS: $OS</li><li>tar: $tar</li><li>tmp: $tmp</li><li>inidir: $inidir</li><li>MODULEDIR: $MODULEDIR</li><li>"
      ."architecture: $architecture</li><li>PHPAPI: $PHPAPI</li><li>phpini: $phpini</li></ul>";
}
 


