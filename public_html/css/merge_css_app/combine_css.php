<?php

  /****************** start of config ******************/
   define('FILE_TYPE', 'text/css'); // type of code we're outputting
   define('CACHE_LENGTH', 3135600); // length of time to cache output file, default approx 1 year
   define('CREATE_ARCHIVE', true); // set to false to suppress writing of code archive, files will be merged on each request
   define('ARCHIVE_FOLDER', 'css/merge_css_app'); // location to store archive, don't add starting or trailing slashes
   
   // files to merge
   $aFiles = array(  
       
       
//     '/css/template/style.css',
//     '/css/template/header-1.css',
//     '/css/template/footer-1.css',
    '/css/test.css'
   );
   
   
   /****************** end of config ********************/
   
   // this is prepended to all file / folder paths so files and archive folder should be specified relative to this
   
   $sDocRoot = $_SERVER['DOCUMENT_ROOT'];
   
   /*
      if etag parameter is present then the script is being called directly, otherwise we're including it in 
      another script with require or include. If calling directly we return code othewise we return the etag 
      representing the latest files
   */
   
   if (isset($_GET['version'])) { //LLEGO AQUI SI YA CARGO UN 12312312321.css
      

      $iETag = (int)$_GET['version'];     
      $sLastModified = gmdate('D, d M Y H:i:s', $iETag).' GMT';
      
      // see if the user has an updated copy in browser cache
      if (
         (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $sLastModified) ||
         (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $iETag)
      ) {
         header("{$_SERVER['SERVER_PROTOCOL']} 304 Not Modified");
         exit;
      }
      // create a directory for storing current and archive versions
      if (CREATE_ARCHIVE && !is_dir("$sDocRoot/".ARCHIVE_FOLDER)) {
         mkdir("$sDocRoot/".ARCHIVE_FOLDER);
      }
      

   
   
      
      // get code from archive folder if it exists, otherwise grab latest files, merge and save in archive folder
      if (CREATE_ARCHIVE && file_exists("$sDocRoot/".ARCHIVE_FOLDER."/site_$iETag.css")) {

        
         $sCode = file_get_contents("$sDocRoot/".ARCHIVE_FOLDER."/site_$iETag.css");
        
                  
      } else {
         
         // get and merge code
         $sCodeO = '';
         $aLastModifieds = array();
         foreach ($aFiles as $sFile) {
            $aLastModifieds[] = filemtime("$sDocRoot/$sFile");
            $sCodeO .= file_get_contents("$sDocRoot/$sFile");
         }
         // sort dates, newest first
         rsort($aLastModifieds);
         
         if (CREATE_ARCHIVE) {
            $iETag = 1567014903;
            
            if ($iETag == $aLastModifieds[0]) { // check for valid etag, we don't want invalid requests to fill up archive folder
            
            $postdata = array('http' => array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query( array('input' => $sCodeO) ) ) );
            
               $sCode = file_get_contents('https://cssminifier.com/raw', false, stream_context_create($postdata));            	
               $oFile = fopen("$sDocRoot/".ARCHIVE_FOLDER."/site_$iETag.css", 'w+');
               
               if(!$sCode){
                   $sCode = $sCodeO;
               }
               
               fwrite($oFile, $sCode);              
               fclose($oFile);
               
            } else {
               // archive file no longer exists or invalid etag specified
               header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
               exit;
            }
         }
         
         
         //OPCION SIN MINIFY
         /*$oFile = fopen("$sDocRoot/".ARCHIVE_FOLDER."/$iETag.cache", 'w');
         fwrite($oFile, $sCode);
         flock($oFile, LOCK_UN);
         fclose($oFile);*/
      }    
      
      
      
   
      // send HTTP headers to ensure aggressive caching
      header('Expires: '.gmdate('D, d M Y H:i:s', time() + CACHE_LENGTH).' GMT'); // 1 year from now
      header('Content-Type: '.FILE_TYPE);
      header('Content-Length: '.strlen($sCode));
      header("Last-Modified: $sLastModified");
      header("ETag: $iETag");
      header('Cache-Control: max-age='.CACHE_LENGTH);
   
      // output merged code
      

      echo $sCode;
      
      

   } else {

   	
      // get file last modified dates
      $aLastModifieds = array();
      foreach ($aFiles as $sFile) {
          
         $aLastModifieds[] = filemtime("$sDocRoot/$sFile");
      }
      // sort dates, newest first
      rsort($aLastModifieds);
      
      // output latest timestamp
      echo $aLastModifieds[0];

   }
?>