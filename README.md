# ClassMapperBundle

>by Damian Piela <damian.piela@tmsolution.pl>

---

### Description

TMSolution ClassMapperBundle is a tool for mapping class names to more friendly equivalents.


### Configuration

To use the bundle, update your `composer.json`:

```
//composer require

"tmsolution/classmapper-bundle": "~1.0"
```


and enable it in the `AppKernel.php`:

```
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Core\ClassMapperBundle\CoreClassMapperBundle();
    );
}
```


Also, change your `config.yml` file similarly to the provided example:

```
core_class_mapper:
     languages:
        pl:
            pl_friendly_name: Entity\Class\Name
        en:
            en_friendly_name: Entity\Class\Name
```
