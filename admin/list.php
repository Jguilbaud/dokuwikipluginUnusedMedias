<?php
/**
 * DokuWiki Plugin unusedmedias (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Johan Guilbaud <guilbaud.johan@gmail.com>
 * @version 1.0 (10/10/2011)
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'admin.php';

//safe mode has to be disabled ! (safe mode is deprecated by php)
@set_time_limit(0);

class admin_plugin_unusedmedias_list extends DokuWiki_Admin_Plugin {

	private $orphans_medias = array();
	private $error 			= "";
	private $ok				= "";
	
    public function getMenuSort() { return FIXME; }
    public function forAdminOnly() { return true; }
   
    public function handle() {
    	global $conf, $ID;

        //$this->_debug_print_r($data);        
        //$this->_debug_print_r($_REQUEST);
        
        
        //If delete requested
        if(isset($_REQUEST['media_id_to_delete'])){

        	if(checkSecurityToken($_REQUEST['sectok'])){
        	
	        	$mediaToDelete = $_REQUEST['media_id_to_delete'];   
				
	        	$file = mediaFN($mediaToDelete);
	        	
	        	if(file_exists($file)){
	        		if(media_inuse($mediaToDelete)===false){
	
					    // trigger an event - MEDIA_DELETE_FILE
					    $data['id']   = $mediaToDelete;
					    $data['name'] = basename($file);
					    $data['path'] = $file;
					    $data['size'] = (@file_exists($file)) ? filesize($file) : 0;
					
					    $data['unl'] = false;
					    $data['del'] = false;
					    $evt = new Doku_Event('MEDIA_DELETE_FILE',$data);
					    if ($evt->advise_before()) {
					        $data['unl'] = @unlink($file);
					        if($data['unl']){
					            addMediaLogEntry(time(), $mediaToDelete, DOKU_CHANGE_TYPE_DELETE);
					            $data['del'] = io_sweepNS($mediaToDelete,'mediadir');
					        }
					    }
					    $evt->advise_after();
					    unset($evt);
	        			
						$this->ok = sprintf($this->getLang('delete_file_ok'),$mediaToDelete);
						;
	        		}else{
	        			$this->error = sprintf($this->getLang('delete_file_in_use'),$mediaToDelete);
	        		}        		
	        		
	        	}else{
	        		$this->error = sprintf($this->getLang('delete_file_not_found'),$mediaToDelete);
	        	}
        	
        	}//end of csrf check
        }
        
        
        //Searching for orphaned medias
        
        $data = array();
        
        //getting all medias
        search($data,$conf['mediadir'],'search_media', array('showmsg'=>true,'depth'=>500),str_replace(':', '/', getNS($ID)));
        

        
        
        //check if they are (still) in use or not.
        foreach($data as $media){
        	$isUsed = media_inuse($media['id']);

        	if($isUsed === false){
        		$this->orphans_medias[$media['id']] = $media;
        	}
        }
        

    }

    public function html() {
    	global $lang, $ID;
    	
        ptln('<h1>' . $this->getLang('title') . ' : '.getNS($ID).'</h1>');
        
        if($this->error != ""){
        	ptln("<div class='error'>".$this->error."</div>");
        }
    	if($this->ok != ""){
        	ptln("<div class='success'>".$this->ok."</div>");
        }
        
        
        if(count($this->orphans_medias)>0){
	        ptln('<table class="inline">');
				ptln('<tr><th class="centeralign">ID</strong></th><th>Actions</th></tr>');
				$i=0;
		        foreach($this->orphans_medias as $id => $media){
		        	ptln('<tr>');
		        	ptln('<td>' . $id . '</td>');
		        	$link = ml($id,'',true);
		        	$btn_view =  ' <a href="'.$link.'" target="_blank">
		        					<img src="'.DOKU_BASE.'lib/images/magnifier.png" alt="'.$lang['mediaview'].'" title="'.$lang['mediaview'].'" class="btn" />
		        				   </a>';
		        	
		        	$btn_delete = ' <form id="unusedmedias_form_'.$i.'_delete" method="post" action="">
		        						<input type="hidden" name="media_id_to_delete" value="'.$id.'" />
		        						<input type="hidden" name="sectok" value="'.getSecurityToken().'" />
		        					</form>
		        					<a href="#" class="btn_media_delete" title="'.$id.'" onclick=\'if(confirm("'.$this->getLang('js_confirm_delete').'")){document.getElementById("unusedmedias_form_'.$i.'_delete").submit();}return false;\'>
		        						<img src="'.DOKU_BASE.'lib/images/trash.png" alt="'.$lang['btn_delete'].'" title="'.$lang['btn_delete'].'" class="btn" />
		        					</a>';
		        	
		        	
		        	ptln('<td>  '.$btn_view.' '.$btn_delete.'</td>');
		        	ptln('</tr>');
		        	$i++;
		        }
		   	ptln('</table>');   
        }else{
        	ptln('<div>'.$this->getLang('nomatches').'</div>');        	
        }
        
       
        
    }
    
    

    
    /**
     * Debug method used only during development.
     * @param array $array
     */
    private function _debug_print_r($array){
    	echo str_replace("\n","<br />",print_r($array,true));
    	echo "<br />";
    }
}

