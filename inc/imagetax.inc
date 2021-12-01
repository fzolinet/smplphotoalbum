<?php
class ImageTax extends Image {
	// Constructor
	Function __construct($id, $Props, $params, $entry='', $entry_old='',$type='image', &$tpl){
	  $this->id         = $id;
	  $this->viewnumber = $Props['viewnumber'];
	  $this->votenumber = $Props['votenumber'];
	  $this->rank       = $Props['rank'];
	  $this->vrank      = $Props['vrank'];
	  $this->subtitle   = $Props['subtitle'];
	  $this->caption    = $Props['caption'];
	  $this->deleted    = $Props['deleted'];
	  $this->thdate     = $Props['thdate'];
	  $this->fb         = $Props['fb'];
	  $this->googlep    = $Props['googlep'];
	  $this->twitter    = $Props['twitter'];
	  $this->url        = $Props['url'];
	  $this->target     = $Props['target'];
		$this->params     = &$params;
		$this->root       = $params['root'];
		$this->path       = $params['path'];
		$this->width      = $params['width'];
		$this->subtitle   = $params['subtitle'];
		$this->caption    = $params['caption'];
		$this->voted      = $params['voted'];
		$this->viewed     = $params['viewed'];
		$this->edit       = $params['edit'] ; //Taxonomy üzemmódban nem lehet szerkeszteni??????
		$this->exif       = $params['exif'];
		$this->style      = $params['style'];
		$this->issocial   = $params['issocial'];
		$this->taxonomy   = $params['taxonomy'];
		$this->types      = $params['types'];
		$this->html5      = $params['html5'];
		
		$this->entry      = $entry;
		$this->name       = $entry;
		$this->entry_old  = $entry_old;
		$this->type       = $type;
		$this->modulepath = $params['modulepath'];
		$this->v          = $params['v'];
		$this->simplelist = isset($_GET['smpllist']) ? true : false;
		$this->tpl        = $tpl;
		
		$path_parts       = pathinfo($entry);
		$this->ext        = (isset($path_parts['extension']))? strtolower($path_parts['extension']):"";

		$this->datetime   = @filemtime($this->root.$this->path.$this->entry_old);
		$this->filesize   = @filesize( $this->root.$this->path.$this->entry_old);
		//@todo:
		$this->caption    = $this->RSArray[$id]->caption;
		$this->sub        = $this->RSArray[$id]->sub;
		
	}
}