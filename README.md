# The Bugsnag Notifier integration for Magento 2.x backend (PHP) and frontend (JS) 
## About
The Bugsnag featured integration for Magento 2 with early start tracking point of handling exceptions for:
- HTTP requests;
- CLI execution;
- JS exceptions handling (frontend).

More information about error reporting to [Bugsnag].

## Configuration
The major part of configurations can be done via `app/etc/env.php` file. Configuration of additional Bugsnag accounts 
  and JS exceptions tracking should be defined via `di.xml` in a separate module.
  
### Configuration via `env.php`

Example of configuration:
```php
<?php
return array (
  'backend' => 
  array (
    'frontName' => 'admin',
  ),
/* [...] */   
/* START OF CONFIGURATION */
  'opt_handler' => 
  array (
    'exceptions' => 
    array (
      'active' => true,
      'limit' => 100,
      'log_path' => 'var/log/bugsnag.log',
      'debug' => 0,
      'exclude' =>         
      array (        
        ':**/vendor/optimlight/magento2-bugsnag/Boot/ExceptionHandler.php' => 
        array (          
          0 => array (0 => 200,1 => 215),
          1 => array (0 => 250,1 => 265),
        ),        
      ),      
      'early_bird' => 
      array (
        'apikey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'severities' => 'fatal,error,warning,parse,notice,info',         
        'guzzle_handler' => '\\Optimlight\\Bugsnag\\Model\\Queue\\Integrator\\Guzzle\\Handler',
        'guzzle_options' => array (),  
        'queue' => 
        array (
          'provider_class' => '\\Optimlight\\Bugsnag\\Model\\Queue\\Provider\\ContextFs',           
          'provider_options' => 
          array (
            'path' => 'var/bugsnag-queue/', 
          ),
          'context_name' => 'bugsnag_queue',
        ),                      
      ),
    ),
  ),
/* END OF CONFIGURATION */
  );
```

Example of configuration with short explanations:
```php
<?php
return array (
  'backend' => 
  array (
    'frontName' => 'admin',
  ),
/* [...] */
   // Main section for configuring the module. 
  'opt_handler' => 
  array (
    'exceptions' => 
    array (
      'active' => true, # [Required] Either the module is active or not.
                        #   The module cannot be  completely disabled via module:disable command.
                        
      'limit' => 100,   # This option defines a maximum number of the same error being reported to Bugsnag during the
                        #   same request/call. For example if the same fragment of code raises an exception 110 times 
                        #   during each request, only first 100 such exceptions will be send to Bugsnag.
                        
      'log_path' => 'var/log/bugsnag.log',
                        # If is set -- error messages would be send to that file. By default messages are sent to
                        #   PHP's system logger.
      
      'debug' => 0,     # Debug level. Is not used intensivly now. Can be used to track how messages are send via
                        #   guzzle (should be set to value bigger then 1).
                        
      'exclude' =>      # [Optional] Defines fragments of code which should not be send to Bugsnag in case of error  
                        #   raised there. Can be useful in case of code fragments which sends too many warnings/notices.
                        #    
      array (
        // Adding `:` at the begging of path will cause using `fnmatch` function with pattern,
        //   instead of strict comparision.   
        ':**/vendor/optimlight/magento2-bugsnag/Boot/ExceptionHandler.php' =>
         
        array (
          // Defining range of lines in file to be excluded: [0] is a start from line (including specified line), [1] - 
          //   last line in a range.
          
          0 => 
          array (
            0 => 200,
            1 => 215,
          ),
          1 => 
          array (
            0 => 250,
            1 => 265,
          ),
        ),
        // ... otherwise absolute path should be specified.
        '/var/www/xyz/vendor/optimlight/magento2-bugsnag/Boot/ExceptionHandler.php' => 
        array (
          0 => 
          array (            
            0 => 100 # Exclude only one line.
          ),
        ),
      ),
      // [Section] There are configurations for the primary Bugsnag notifier.
      //   It will be created during the collecting of `registration.php` files and therefore before
      //   application's framework will be loaded. 
      'early_bird' => 
      
      array (
        # [Required] API key from the Bugsnag account for the project.
        'apikey' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 
        
        # [Optional] Levels of errors' to be tracked. See: \Bugsnag\ErrorTypes class for more info.     
        'severities' => 'fatal,error,warning,parse,notice,info',
        
        # [Optional] Is required in case of enabling queue instead of sending tracked errors during shutdown callback.
        'guzzle_handler' => '\\Optimlight\\Bugsnag\\Model\\Queue\\Integrator\\Guzzle\\Handler', 

        // [Section] Settings for the queue (if is enabled by previous config).
        'queue' => 
        array (            
          # [Required] The class responsible for serving queue. Currently is supported queue to the files.
          #   To use other options (like Gearman, Redis, etc) see: https://github.com/php-enqueue/enqueue-dev
          'provider_class' => '\\Optimlight\\Bugsnag\\Model\\Queue\\Provider\\ContextFs',   
          
          // [Section] Additional options for the queue.
          'provider_options' => 
          array (            
            # [Required] Path to a directory where to store queue's files (in case of `ContextFs`). 
            'path' => 'var/bugsnag-queue/', 
          ),
          
          # [Optional] Default name of the queue used by EarlyBird Notifier.
          'context_name' => 'bugsnag_queue',
        ),
        
        // [Section] Additional options for the Guzzle HTTP Client.
        'guzzle_options' => 
        array (),
        
        # [Optional] Class used to gain the number of current build (CI/CD process).
        'build_class' => '\\Optimlight\\Bugsnag\\Model\\Resolver\\Build\\JsonFile',
        
        // [Section] In case of CI/CD can be configured how to get build information to send it to Bugsnag.
        //   More info: https://docs.bugsnag.com/api/build/
        'build_options' => 
        array (
          'type' => 'file',                  # Type of the source (can be http or file).
          'destination' => 'var/build.json', # Path to the file with build info.
          'path_info' => 'info/build',       # Current case is JSON file with build info. This key is a path for an nested structure with build info.
          'path_version' => 'version',       # This key is a path to a key inside nested structure retrieved via previous  key.
                                             # Example: {"action": "deploy", "info": {"build": {"time": "2018-07-20 12:00:00", "version": "2.1.0"}}}
                                             # To get version key we need path: "info/build", and then "version".
        ),
      ),
    ),
  ),
  );
```

### Configuration via `di.xml`
- [ ] TODO    

## TODO
- [X] Plugin for cron (need more testing).
- [X] Auto-deploy include file (for file `setup/config/autoload/bugsnag.local.php`).
- [X] Queue for messages.
- [X] Send build info only once (cache).
- [ ] Finish readme document.
- [ ] Encryption for stored records.
- [ ] Get build info from env variables.
- [ ] Unit tests.

## Notes
- Even being disabled via `bin/magento module:disable`, but with `active` flag set to `true` in env.php, extension
  will be working.
