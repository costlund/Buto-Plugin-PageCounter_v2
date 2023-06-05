# Buto-Plugin-PageCounter_v2
Write page hits to an mysql db with a backend to monitor.


## Backend
For users with role webmaster or webadmin.
```
plugin_modules:
  counter:
    plugin: 'page/counter_v2'
    settings:
      mysql: 'yml:/_php_settings_.yml'
```

### Url
- /counter/start


## Settings

```
plugin:
  page:
    counter_v2:
      settings:
        mysql: 'yml:/_php_settings_.yml'
        list_all:
          limit: 500 (optional, default 1000, limit rows in list all)
```

## Event

```
events:
  module_method_before:
    -
      plugin: 'page/counter_v2'
      method: count
      data:
```

### Filter
Add filter params to filter away requests.
```
        filter:
```
#### Class
Class param sys/class in Globals.
```
          class:
            - 'qr'
```

### URI
Server variable REQUEST_URI.
```
          uri:
            - '*contact*'
```

## Schema
```
/plugin/page/counter_v2/mysql/schema.yml
```

