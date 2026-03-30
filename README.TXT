
Simple Photoalbum 1.9.x-xxxx
~~~~~~~~~~~~~~~~~~~~~~~~~
Features
~~~~~~~~
- The modul works as a Drupal filter of any nodes on it.
- It makes a table / list of images, videos, documents compressed or executable files 
  from a directory on the server.
- When the node is viewed pushed the Refresh button the module makes 
  - thumbnails from the images,
  - the thumbnails saves into the _tn_ subdirectory,
  - save the datas of files into the database table 
- makes automatically thumbnails and insert into a table of database.

Dependencies
~~~~~~~~~~~~
- Before jQuery UI is in the core of Drupal and there is no dependencies.
- From Drupal 9.5.1 the jQuery UI is not in the core of Drupal and 
  download and install the contrib modules from here
  - jQuery UI: https://www.drupal.org/project/jquery_ui
  - jQuery UI Draggable:https://www.drupal.org/project/jquery_ui_draggable
  - jQuery UI Resizable: https://www.drupal.org/project/jquery_ui_resizable

Installation:
~~~~~~~~~~~~
- Download and install JQuery UI Jquery UI Draggable and jQuery UI Resizable.
- Copy the files of module into the modules directory and enable.
- Set the appropriate properties in the admin/config/fz/smplphotoalbum   
- In the admin/config/content/formats
  Enable the Simple Photoalbum filters in the Full HTML type.
- Default: The image shown in the other blank window.

Defaults
~~~~~~~~
- The thumbnails showed in table format (css flex)
- The table has pager
- The admin sets what kind of files enables / disables. It can 
  - image (enables default), 
  - videos (enables default) normal or HTML5 Video format
  - audio (enables default) HTML5 Audio format
  - document,
  - compressed,
  - executable,
  - other types, 
  - prohibited 
  files.
- The admin can change the extension of types.

Events
~~~~~~
If you click on a thumbnail:
- image: showing in original size
- Video: downloads or plays
- HTML5 Video - plays
- audio: downloads or plays
- HTML5 Audio - plays
- documents, compressed, executable, other types downloads

Administration settings
~~~~~~~~~~~~~~~~~~~~~~~
- Maximum number of items on a page
- Maximum width of thumbnail: (default 150px)
- CSS class of thumbnails (default: smpl_image)
- Root folder of photoalbums:
  This is absolute path in the filesystem. If you use in the path the 
  __DRUPALROOT__ then it substitutes the Drupal path with it.
- Rebuild of pathes of menus (for developers)
- Shows the URL of associated with items (enable/disable)
- Default target of link of image (default: _blank)
- Using taxonomy Photoalbum (enable/disable) - Under Construction

On the page there are buttons for admin 
- File upload - upload a file into the folder (if upload enabled)
- New Folder - makes a new folder if it is enabled centralized or on this node
- Edit Path - Edit subtitles of items of this path
- Clear the caches of Drupal and Opcache
- Refresh - Refresh or make thumbnails of this path
- Play - plays a slideshow from images of this path
- Help 

The admin can enable / disable the  
- Shows the number of views (downloads) of images (files)
- Exif and other properties of images (exif info, bitrate, etc.) and documents
- Substitute of item
- Link of files (URL of original website etc...)
- importance of item

- There are buttons below the files and you can
  - Edit images online (if installed GD or Imagic module on PHP)
  - Edit the subtitles of images with AI, link, importance, filename of items 
  - Delete from database and directory 

Simple Photoalbum temporary folder for Editing the images
- Change the order of files / images
- Using filter in lists (enable / disable)   
- Check and delete the wrong links from database
- Change or delete the subtitles automatically (string substitues with a space)
- File types (enable / disable). Let's see on top of this document
- Set the lists of extensions of any types

Using:
~~~~~

You want to make a simple photoalbum:

  [smplphotoalbum]
    {name_of_parameter: value}
    ...
  [/smplphotoalbum]

  {smplphotoalbum}
    {name_of_parameter: value}
    ...
  {/smplphotoalbumn}
    
Parameters
~~~~~~~~~~
  notes: some-text
  number: number of images on one page (from D8)
  width: width of thumbnails in pixel
  order: asc /desc / rand, default asc
  lang: en|hu, default: en
  ascdesc: asc /desc, default asc (D8)
  sort: sortable of this album - (True/False)
  sub: is there subtitle (true|false)
  viewed: shows the number of views
  edit: Editable images (true|false)
  exif: shows the exif onformation of images
  audio: shows this type of files (true|false)
  video:shows this type of files (true|false)
  doc:shows this type of files (true|false)
  cmp:shows this type of files (true|false)
  app:shows this type of files (true|false)
  url: shows the URL of image website
  target: what is the target (default: _blank)
  html5: the video and autio file shows with HTML5 audio and video tags
  autoclose: After save the edited image the edit windows auto close (true|false)
  ai: name of image from image recognition of AI
  method: GET / POST
  upload: can admin upload enabled file types into the photoalbum
  
  Watermark
    wm: Watermark when show|edit the image (true|false)
    wmpath: Watermark path 
      From public folder (beginning of public://)
      From Simple Photoalbum module default:(beginning of smplphotoalbum://images/). 
    wmalpha: watermark alpha value: 0..100 (in %). <br/>0 - invisible, 100 - max, default: 10
    wmcopyright: any text into copyright IPTC/Exif field
    wmauthor: any text into author / Owner IPTC/Exif field   
  
  test: test mode, only the  true|false
  graphicdrv: which graphic driver use to edit?  gd or imagick (if it installed)
  
  lang: choose language file - hu |en
  translate: do you want to use translate d interface - true|false
  icon: Set the color of icons on the page (_col / _bw)

  slide: True - Let slideshow. The parameters of slideshow  
    title: - title of slideshow
    interval: time of changin in milisec  
    style: Mode of changing images - fade / slide / zero / zero1
    slstyle: string of css settings of the main image. Default: 'max-width: 100%; height: 600px;'
  
Example default
~~~~~~~~~~~~~~~
    [smplphotoalbum]
      {path: /teszt}
      {number: 12}
      {width: 180px}
    [/smplphotoalbum]<br>
  
Example for slideshow
~~~~~~~~~~~~~~~~~~~~~   
    [smplphotoalbum] 
      {title: Bassguitar}
      {path:/2004_Bassguitar}
      {slide: true}
      {interval: 10000}
      {height: 800px}
      {style: fade}
    [/smplphotoalbum]

Edit An image (this is under construction)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
If you are administrator you can edit the images online
- You can edit the name of file, subtitle (caption) 
- You can edit a lot of properties of image, for example
-- Rotate, flip vertically, horizontally
-- Crop the image
-- Resize the image
-- Make Watermark over the image (Copyright and author info into jpeg file)
-- Make Border and Vignette
-- Gamma, Brightness, Saturation, Hue, RGB, Grayscale, Contrast
-- Add Noise, Denoise
-- Sharpen, Emboss, Edge, Blur and Convolution (with convolution matrix)

It is uses the GD driver or Imagick driver if it is installed on server

If the change takes too long you can break it with press the Escape key or 
push the Cancel button.

Convert an video (under construction)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
with ffmpeg on Linux or Windows
- crop the video
- change the length 
- change the extension of video ( i.e. .mov => mp4 )
- change the framerate
- rotate
- Break the long conversion process with Escape key or Cancel button

The using is similar than ACDSee or any other image editing software.
You can use the mouse to move, zoom in or zoom out the image.

2026.02.19

Zoltan Fabian

fz@fzolee.hu
http://www.fzolee.hu
