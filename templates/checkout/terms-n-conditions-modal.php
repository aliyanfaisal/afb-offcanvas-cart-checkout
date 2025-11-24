   
<style>
	/* trigger link */
    #terms-n-conditions { 
		color: #232323 ;
      text-decoration: none;
      cursor: pointer;
    }
	
	#terms-n-conditions:hover{
		color: black;
      text-decoration: underline;
	}

    /* modal wrapper hidden by default */
    #modal {
      position: fixed;
      inset: 0;
      display: none;
      z-index: 23423423423423;
    }

    /* show when targeted */
    #modal:target {
      display: block;
    }

    /* invisible background link (acts as outside click) */
    #modal .bg {
      position: absolute;
      inset: 0;
      display: block;
      text-decoration: none;
    }

    /* modal content */
    #modal .content {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: white;
      padding: 1rem;
      border: 2px solid #333;
      max-width: 600px;
      z-index: 1;
	  max-height: 96vh;
      overflow-y: auto;
	  margin-top: 3vh
    }
	
	.afb-modal-content h1{
		font-size: 1.375rem !important;
	}
	.afb-modal-content , .afb-modal-content p{
		font-size: 10px !important;
	}
  </style>
 
 

  <div id="modal">
    <a href="#" class="bg"></a> <!-- this closes when clicked outside -->
    <div class="content afb-modal-content">
      <div class="js-modal-content">

		   <?php
            // Language-specific post IDs
            $lang_terms_post_ids = [
                ""   => 5727, // default
                "en" => 5770,
                "fr" => 5768
            ];

      
            $current_uri = $_SERVER['REQUEST_URI'];
 
            $lang_slug = "";

         
            if (preg_match("#^/([a-z]{2})/#", $current_uri, $matches)) {
                $possible_lang = $matches[1];
                if (array_key_exists($possible_lang, $lang_terms_post_ids)) {
                    $lang_slug = $possible_lang;
                }
            }

           
            $post_id = $lang_terms_post_ids[$lang_slug];
 
            // Output the content of the correct post
            $post_content = get_post_field('post_content', $post_id);
		  echo $post_content;
//             echo apply_filters('the_content', $post_content);
            ?>

      </div>
    </div>
  </div>
