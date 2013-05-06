----------------------
Extra: phpThumbsUp
----------------------
Version: 1.0.0
Developed By: Darkstar Design (info@darkstardesign.com)

An alternative to the popular phpThumbOf for creating dynamic thumbnails of images.

phpThumbsUp is an alternative to phpThumbOf with several key differences. The snippet uses the exact same syntax as
phpThumbOf, so you can simply replace your :phpThumbOf=`...` calls with :phpThumbsUp=`...`


----------------------
Key Features
----------------------

* System setting to create thumbnails when an image is uploaded via the file manager instead of on page render.
* System setting to clear the phpThumbsUp cache when the site cache is cleared.
* Creates thumbnails via a plugin rather then a snippet, meaning a page does not have to wait on a thumbnail to be
  created before it is displayed.
* Thumbnails are created based on URLs (i.e. mysite.com/phpthumbsup/w/400/h/300/zc/1/src/path/to/image.jpg) instead of
  snippet calls, making it easy to dynamically create thumbnails on the fly.
* For a full list of features, see http://www.darkstardesign.com/resources/phpthumbsup


----------------------
Installation
----------------------

Installation is straightforward. Install the package through the ModX manager under System => Package Management.


----------------------
Usage
----------------------

*** URL ***

phpThumbsUp uses a specific URL format to know when it needs to create thumbnails. By default, any url path starting
with phpthumbsup tells phpThumbsUp "this is a thumbnail" (you can change this to whatever you want in system settings).
The next part of the URL specifies the filters. These are the exact same filters used by phpThumbOf, as they are passed
to the ModX service modPhpThumb. /src/ is used to specify the end of the filters and beginning of the path to the image.

A quick example:

mysite.com/phpthumbsup/w/400/h/300/zc/1/src/path/to/image.jpg
         [1. base_url][   2. filters   ]   [  3. image url   ]

Let's break down this example:

1. /phpthumbsup is how we know this is a thumbnail. This is required at the beginning of every thumbnail
2. /w/400/h/300/zc/1 are the filters passed to modPhpThumb
3. /src specifies the end of the filters and beginning of the path to the image
   /path/to/image.jpg is the url that would be used to access the image (relative to site root)

*** Snippet ***

For those of you used to using phpThumbOf, there is a snippet that accepts the exact same input as phpThumbOf and
converts it to the correct URL format. For example, [[*image:phpThumbsUp=`w=400&h=300&zc=1`]] will generate the above
URL format for you.

*** File Manager ***

One of the key features of phpThumbsUp is the ability to automatically create thumbnails when an image is uploaded via
the file manager instead of when a page is rendered. To enable this, you simply need to define filters and paths in the
system settings phpthumbsup.auto_create using a phpThumbsUp URL. Separate multiple directories with a colon.

Example (notice you don't include the /phpthumbsup part at the beginning):

/w/400/h/300/zc/1/src/path/to/dir:/w/800/src/path/to/dir

This would create thumbnails 400x300 and 800xAuto anytime an image is uploaded to /path/to/dir (relative to site root)


----------------------
System Settings
----------------------

There are system settings available to customize phpThumbsUp to your needs. As a general rule, DO NOT edit the path
settings unless you are an advanced user of ModX and have a customized installation.

* phpthumbsup.auto_create - colon separated list of directories using phpThumbsUp URL format
* phpthumbsup.base_url - the first part of phpThumbsUp URL (default: phpthumbsup/)
* phpthumbsup.clear_cache - should thumbnail cache be cleared when site cache is cleared? (default: yes)
* phpthumbsup.cache_path - the path to the phpThumbsUp cache
* phpthumbsup.core_path - the core path to phpThumbsUp