# Bing Search Extension

Bing search extension for mediawiki framework.

### Installation

```sh
$ Go to mediawiki extensions folder "cd /var/www/html/mediawiki/extensions"
$ git clone https://github.com/abedzoubi26/BingSearch.git
$ Edit your "LocalSettings.php" file by adding the following lines at the end of file:
   "Load BingSearch extension."
   wfLoadExtension('BingSearch');
   "Bing search access key."
   $wgBingSearchAccessKey = "0a90a24c1c4346529c6816de94728e62";
   $wgBingSearchApi = "https://api.cognitive.microsoft.com/bing/v7.0/search";
   $wgTableName = "saved_results";
$ Then go back to /var/www/html/mediawiki/
$ And run "php maintenance/update.php"
```

##### After finish
- Go to http://localhost/mediawiki/index.php/Special:SpecialPages
- find group called "Bing Search Extension" which contains the links to special pages

##### NOTE: To see saved results you need to be logged in.
