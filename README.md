# vivaldiSuggestSearch

A little script intended to provide search suggestions on Vivaldi for some unsupported search engines

## Installation

### Depends

* A Web server (Nginx, Apache, etc.)
* PHP

### Preparation

1. Add the following line to your hosts file (on Linux: `/etc/hosts`):

    127.0.0.1  suggest.loc

2. Copy the index.php file on your server:

    mkdir /var/www/html/autosuggest
    cp index.php /var/www/html/autosuggest

3. Configure the vhost. Example of working configuration for Nginx:

```
server {
  listen 80;
  server_name suggest.loc;

  root /var/www/html/autosuggest;
  index index.html;

  location ~ \.php$ {
    # regex to split $uri to $fastcgi_script_name and $fastcgi_path
    fastcgi_split_path_info ^(.+\.php)(/.+)$;

    # Check that the PHP script exists before passing it
    try_files $fastcgi_script_name =404;

    # Bypass the fact that try_files resets $fastcgi_path_info
    # see: http://trac.nginx.org/nginx/ticket/321
    set $path_info $fastcgi_path_info;
    fastcgi_param PATH_INFO $path_info;

    fastcgi_index index.php;
    fastcgi_pass unix:/run/php-fpm/php-fpm.sock; # Depending on your php-fpm config
  }
}
```

4. Reload your web server configuration

### Usage

Put the following line as suggestion URL on you Vivaldi search engine configuration:

`http://suggest.loc/?t={type}&q=%s`

replacing `{type}` by the name of your search engine:

* For Qwant: qwant
* For Allocine: allocine

### Add suggestion

To add a new search suggestion, you’ll need to modify 3 parts of the index.php:

1. add a type name and the official API suggestion URL on the array $types
2. create a transformation function which takes the official requested json and returns an array of the wanted suggestions
3. add a case for applying this function to your created type
