<?php 

function google_adsense() {
    
    return 1;
    

}

function tradedoubler($site) {

    global $show_adsense;

    $show_adsense = 0;
    
    ?> 
    
    <div class="text-center">
        <?php if($site == "lolamarket_728x90v2") { ?>        
        	<script type="text/javascript">
            	var uri = 'https://impes.tradedoubler.com/imp?type(img)g(24300626)a(3045060)' + new String (Math.random()).substring (2, 11);
            	document.write('<a href="https://clk.tradedoubler.com/click?p=289117&a=3045060&g=24300626" target="_BLANK"><img src="'+uri+'" border=0></a>');
            </script>            
        <?php } else if($site == "bookingcom_120x600v1") { ?>        
        	<script type="text/javascript">
    			var uri = 'https://impes.tradedoubler.com/imp?type(img)g(23537594)a(3045060)' + new String (Math.random()).substring (2, 11);
    			document.write('<a href="https://clk.tradedoubler.com/click?p=274879&a=3045060&g=23537594" target="_BLANK"><img src="'+uri+'" border=0></a>');
    		</script>    		
    	<?php } else if($site == "bookingcom_120x600v3") { ?>    		
    		<script type="text/javascript">
            	var uri = 'https://impes.tradedoubler.com/imp?type(img)g(23537598)a(3045060)' + new String (Math.random()).substring (2, 11);
            	document.write('<a href="https://clk.tradedoubler.com/click?p=274879&a=3045060&g=23537598" target="_BLANK"><img src="'+uri+'" border=0></a>');
            </script>    		
        <?php } else if($site == "bnext_728x90junio2018") { ?>            
        	<script type="text/javascript">
                var uri = 'https://impes.tradedoubler.com/imp?type(img)g(24311566)a(3045060)' + new String (Math.random()).substring (2, 11);
                document.write('<a href="https://clk.tradedoubler.com/click?p=290998&a=3045060&g=24311566" target="_BLANK"><img src="'+uri+'" border=0></a>');
            </script>            
        <?php } else if($site == "bookingcom_200x200v2") { ?>
        	<script type="text/javascript">
            	var uri = 'https://impes.tradedoubler.com/imp?type(img)g(23537596)a(3045060)' + new String (Math.random()).substring (2, 11);
            	document.write('<a href="https://clk.tradedoubler.com/click?p=274879&a=3045060&g=23537596" target="_BLANK"><img src="'+uri+'" border=0></a>');
            </script>
        <?php } else if($site == "bookingcom_250x250v5") { ?>
        <script type="text/javascript">
        	var uri = 'https://impes.tradedoubler.com/imp?type(img)g(23537602)a(3045060)' + new String (Math.random()).substring (2, 11);
        	document.write('<a href="https://clk.tradedoubler.com/click?p=274879&a=3045060&g=23537602" target="_BLANK"><img src="'+uri+'" border=0></a>');
        </script>
        <?php } ?>
    </div>
    
<?php }

?>