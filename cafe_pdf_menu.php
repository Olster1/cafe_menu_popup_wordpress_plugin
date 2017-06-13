<?php
   /*
   Plugin Name: cafe menu pdf uploader
   Plugin URI: http://edgeeffectmedia.com
   Description: a plugin to upload a cafe/restaurant menu and view it as a modal popup
   Version: 1.0
   Author: Oliver Marsh
   Author URI: http://edgeeffectmedia.com
   License: GPL2
   */


   function enqueue_styles() {
    
    wp_enqueue_style( plugin_dir_path( __FILE__ ), plugin_dir_url( __FILE__ ) . 'css/styles.css', array(), 090617, 'all' );

   }
   add_action( 'wp_enqueue_scripts', 'enqueue_styles' );

   function enqueue_scripts() {
    wp_enqueue_script( plugin_dir_path( __FILE__ ), plugin_dir_url( __FILE__ ) . 'js/modal_menu.js', array(), 090617, false ); 
   }
   add_action( 'wp_enqueue_scripts', 'enqueue_scripts' );

   add_action('admin_menu', 'test_plugin_setup_menu');
    
   function test_plugin_setup_menu(){
           add_menu_page( 'Cafe Menu Plugin Page', 'Cafe Menu Upload', 'manage_options', 'test-plugin', 'test_init' );
   }
    
   function test_init(){
        test_handle_post();
?>
        <h1>Cafe Menu</h1>
        <h2>Upload a File</h2>
        <!-- Form to handle the upload - The enctype value here is very important -->
       <!--  <form  method="post" enctype="multipart/form-data">
                <input type='file' id='test_upload_pdf' name='test_upload_pdf'></input>
                <?php //submit_button('Upload') ?>
        </form> -->
        <p>To insert it into wordpress use the shortcode <code>[cafe_menu_popup="1"]</code></p>
        <canvas id="the-canvas" style="border:1px solid black; display: none; "></canvas>
          <input id='pdf' type='file'/>
          <div id="server-response"></div>
          <!-- Use latest PDF.js build from Github -->
          <script type="text/javascript" src="https://rawgithub.com/mozilla/pdf.js/gh-pages/build/pdf.js"></script>
          
          <script type="text/javascript">

            //
            // Disable workers to avoid yet another cross-origin issue (workers need the URL of
            // the script to be loaded, and dynamically loading a cross-origin script does
            // not work)
            //
            PDFJS.disableWorker = true;
            //
            // Asynchronous download PDF as an ArrayBuffer
            //
            var pdf = document.getElementById('pdf');

            pdf.onchange = function(ev) {
              if (file = document.getElementById('pdf').files[0]) {
                document.getElementById("server-response").innerHTML = "<p style='background-color: yellow;'>Please Wait...Converting and Saving pdf as png.</p>";
                fileReader = new FileReader();
                var imageSrcString = "";
                var numPages = 0;
                fileReader.onload = function(ev) {
                  PDFJS.getDocument(fileReader.result).then(function getPdfHelloWorld(pdf) {
                      numPages = pdf.numPages;
                      var indexAt = 1;
                      pdf.getPage(indexAt).then(handlePages);
                      function handlePages(page) {
                        var scale = 1.5;
                        var viewport = page.getViewport(scale);
                        //
                        // Prepare canvas using PDF page dimensions
                        //
                        var canvas = document.getElementById('the-canvas');
                        var context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        //
                        // Render PDF page into canvas context
                        //
                        // console.log(viewPorts);
                        var task = page.render({canvasContext: context, viewport: viewport})
                        task.promise.then(function(){
                          imageSrcString += "cafe_menu_image_" + indexAt + "=" + canvas.toDataURL('image/png') + "&";
                          if(indexAt === numPages) {
                            imageSrcString += "numOfImages=" + numPages;
                            ajaxCall(imageSrcString);        
                          } else {
                            indexAt++;
                            context.clearRect(0, 0, canvas.width, canvas.height);
                            pdf.getPage( indexAt ).then( handlePages );
                          }
                        });
                    }
                  }, function(error){
                    console.log(error);
                  });
                };
                fileReader.readAsArrayBuffer(file);
                
              }
            }
            function ajaxCall(ImageSrcString) {
              var xmlhttp = null;
              if (window.XMLHttpRequest) {
                  // code for modern browsers
                  xmlhttp = new XMLHttpRequest();
               } else {
                  // code for old IE browsers
                  xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
              }
              xmlhttp.onreadystatechange = function() {
                  if (this.readyState == 4 && this.status == 200) {
                     // Typical action to be performed when the document is ready:
                     document.getElementById("server-response").innerHTML = xmlhttp.responseText;
                     //To show user what they uploaded
                     // var elm = document.getElementById("server-response");
                     // var image = new Image();
                     // image.src = ImageSrc;
                     // elm.appendChild(image); 
                  }
              };
              // console.log(JSON.stringify(ImageSrc));

              
              xmlhttp.open("POST", ajaxurl, true);
              xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
              // console.log(ImageSrcString);
              xmlhttp.send("action=backend_ajax&" + ImageSrcString);

            }
          </script>
<?php
}
 
function test_handle_post(){
        
}

add_action( 'wp_ajax_backend_ajax', 'backend_ajax' );  

function backend_ajax() {
  $greenStyle = "style='background-color: green;'";
  $redStyle = "style='background-color: red;'";
  if(isset($_POST['numOfImages'])){
    $numOfImages = $_POST['numOfImages'];
    $url_of_folder = plugin_dir_url(__FILE__);
    for($i = 1 ; $i <= $numOfImages; ++$i) {
      $name = 'cafe_menu_image_' . $i;
      if(isset($_POST[$name])) {
        $img = $_POST[$name];
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $fileData = base64_decode($img);
        $fileName = "image" . $i . ".png" ;
        $fileNameUrl = plugin_dir_url(__FILE__) . $fileName;
        $fileNamePath = plugin_dir_path(__FILE__) . $fileName;

        if(file_put_contents($fileNamePath, $fileData)) {
          echo "<p " . $greenStyle . ">File '" . $name . "' upload successful!</p>";
        } else {
          echo "<p " . $redStyle . "> Didn't write file! :(</p>";
        }

      } else {
        echo "<p " . $redStyle . "> Opps!, " . $name . " not found</p>";
      }
    }
    $name = "cafe_menu_image_folder_url";
    if(!add_option($name, $url_of_folder)) {
            update_option($name, $url_of_folder);
    }

    $name = "cafe_menu_image_number";
    if(!add_option($name, $numOfImages)) {
            update_option($name, $numOfImages);
    }
  } else {
    echo "<p " . $redStyle . "> no data passed </p>";
  }
}

add_shortcode("cafe_menu_popup", "shortcode_output");

function shortcode_output() {

  // $a = shortcode_atts(array('id'=>'-1'), $atts);
  // // No ID value
  // if(strcmp($a['id'], '-1') == 0){
  //         return "";
  // }
  // $pdf=$a['id'];
  // $url=plugin_dir_url(__FILE__)."output/".$pdf."/".$pdf."/index.html";

   ?>
  <button id='myBtn'>Open Menu</button>

  <div id='myModal' class='modal'>

    <div class='modal-content'>
      <span class='close'>&times;</span>
      <div id="menu-img-id"></div>
    </div>
  </div>
  <script>
    var elm = document.getElementById("menu-img-id");
    var menuImageFolderUrl = "<?php echo get_option("cafe_menu_image_folder_url"); ?>";
    for(var i = 1; i <= <?php echo get_option("cafe_menu_image_number");?>; ++i) {
      var menuImage = new Image();
      menuImage.src = menuImageFolderUrl + "image" + i + ".png";
      menuImage.className = "modal-image";
      elm.appendChild(menuImage);
    }
  </script>
<?php
}
?>