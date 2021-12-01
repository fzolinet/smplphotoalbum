<?php
class ImageListTax extends ImageList{
	/**
	 * Make the list of objects of Taxonomy images
	 * @param unknown $params
	 */
	function __construct(&$params){
		$this->preSettings($params);
		$root             =  $params['root'];

		$tax='';
		foreach( $params['taxes'] AS $i => $t){
			$tax .= "'" . $t . "',";
		}
		$tax .= "'--##--'";
		$smpl_tax = variable_get('smpl_taxonomy_vocabulary_machine_name','smpl_vocabulary');
		$voc = taxonomy_vocabulary_machine_name_load($smpl_tax);
		
		//Query of this folder
		$sql  = "SELECT s.id, s.path, s.name, d.name as taxname, s.viewnumber, s.votenumber, s.rank, s.caption, s.subtitle, s.deleted, s.thdate, s.fb, s.googlep, s.twitter, s.url, s.target \n";
		$sql .= "  FROM \n";
		$sql .= "    {smplphotoalbum} AS s,\n";
		$sql .= "    {smplphotoalbum_taxonomy} AS t,\n";
		$sql .= "    {taxonomy_term_data} AS d \n";
		$sql .= "WHERE \n";
		$sql .= "  s.id = t.sid AND\n";
		$sql .= "  NOT (s.deleted) AND\n";
		$sql .= "  d.tid    = t.tid AND\n";
		$sql .= "  d.vid    = ".(isset($voc->vid) ? $voc->vid : "d.vid")." AND \n";
		$sql .= "  d.name in (".$tax.")\n";
		$rs = db_query($sql);
		$db = $rs->rowCount();

    //html head metatags
	  $this->addMetaKeyword($tax);
	  $this->addMetaDescription($tax);
	  
		$ok = false;
		$i = 0;
		foreach($rs as $record)
		{
			$entry                  = $record->name;
			$path                   = $record->path;
			$this->RSArray[$record->id] = $record;

			$type  = false;
			if( ( $this->is_image($entry) or
			    ( $this->video and $this->is_videohtml5($entry) ) or
					( $this->video and $this->is_video($entry) ) or
			    ( $this->audio and $this->is_audiohtml5($entry) ) or
					( $this->audio and $this->is_audio($entry) ) or
					( $this->doc 	 and $this->is_doc  ($entry) ) or
			    ( $this->cmp   and $this->is_cmp  ($entry) ) or
			    ( $this->app   and $this->is_app  ($entry) ) or
			    ( $this->dis and !($this->is_dis  ($entry)))
				)
		  )
			{

				$filtered = false;
				if( $this->firstimage <= $i && $i < $this->firstimage + $this->pagelength)
				{
					$type      = $this->smpl_type($entry);
					$entry_old = $entry;
					$entry     = utf8_encode($entry);
					$params['path'] = $record->path;
					
					//Properties of actual Image
					$ImgProps['viewnumber'] = $record->viewnumber;
					$ImgProps['votenumber'] = $record->votenumber;
					$ImgProps['rank'      ] = $record->rank;
					$ImgProps['vrank'     ] = ($record->votenumber >0 )? (float) ($record->rank / $record->votenumber ):0.0;
					$ImgProps['subtitle'  ] = $record->subtitle;
					$ImgProps['caption'   ] = $record->caption;
					$ImgProps['deleted'   ] = $record->deleted;
					$ImgProps['thdate'    ] = $record->thdate;
					$ImgProps['fb'        ] = $record->fb;
					$ImgProps['googlep'   ] = $record->googlep;
					$ImgProps['twitter'   ] = $record->twitter;
					$ImgProps['url'       ] = $record->url;
					$ImgProps['target'    ] = $record->target;
					$ImgProps['datetime'  ] = @filemtime($this->root.$this->path.$entry_old);
					$ImgProps['filesize'  ] = @filesize( $this->root.$this->path.$entry_old);
					
					$this->aPictures[$this->ImageNumber] = new ImageTax($record->id,
							$ImgProps,
							$params,
							$entry,
							$entry_old,
							$type,
							$this->tpl
					);

					//Filtered images
					if($this->filter && $this->setFilter($this->aPictures[$this->ImageNumber] ) ){
						unset($this->aPictures[$this->ImageNumber]);
						$filtered = true;
					}else{
						$this->ImageNumber++;
					}
				}
				if(! $filtered) $i++;

			} //if
		} // foreach
		//Change the orderof images
		if($this->change_order){
			//$this->setOrder();
		}

		$this->pagenumber = (int) ($i/ $this->pagelength)+1;

		//If thepage number higher than should be
		if ($this->page > $this->pagenumber) $this->page = $this->pagenumber;

		//The number of rows in actual page
		$this->RowNumber = (int) ($this->ImageNumber / $this->cols) + (($this->ImageNumber % $this->cols )>0 ? 1 : 0);
	}
}