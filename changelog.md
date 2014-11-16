#Versions
After a long period of being too lazy to implement a trustworthy version number mechanism and trusting on revision numbers and release dates, Konsolidate now sports a true semver version number.
A lot of pondering went into the correct determination of the current version number, depending on what was considered 'a significant change' Konsolidate would end up somewhere between major versions 7 and 21. This would be strange and also require the history to be tampered with, so we simply went with dubbing the current stable version to v1.0.0.

##Compatibility
In order to enable Konsolidate to be properly refactored to the standards of 2014 and to allow developers to still maintain the high level of backward compatibility they've grown accustomed to, we will provide compatibility tiers whenever possible.

###Tiers
If you have developed a project agains a specific major version (currently v1), you can load the intermediate compatibility tiers in order to become remain compatible whilst maintaining the option to update Konsolidate.
These compatibility tiers serve two purposes;
- Keep your project up and running without forcing you to rewrite all of your code _now_ (you will have to adapt eventually, but rather at a time convenient to you (and your client))
- Provide you with examples on how to implement the changes yourself, so you can choose which approach serves your purpose best.
__Be aware that even though we will provide compatibility tiers, you still need to reference them yourself when your have extended or added functionality in class extends__. If your class extends from anything in the 'Core'-tier that has undergone an API change, you need to change the extend to the compatibility tier of choice, e.g.
`class SiteConfigINI extends CoreConfigINI` may need to become `class SiteConfigINI extends OneConfigINI`.

Any compatibility tier will only provide compatibility with the previous version, as always, you can load as many tiers as you like, so in theory it should be possible to keep your v1-based project up and running over many years and across multiple versions of both PHP and Konsolidate. The compatibility tiers will be named after the version of Konsolidate they provide compatibility for, e.g. the tier providing compatibility with Konsolidate v1.x will be named 'One', obviously the 'One'-tier will be available in v2.x, the 'Two'-tier will be available in v3.x and so on.


##General changes
###ASCII Art
We have removed all of the Konsolidate ASCII art except for the Konsolidate class file itself.

###Filenames
Konsolidate no longer requires to rather explicit '.class.php' file extension, but instead now allows for the '.php' file extension. In order to preserve compatibility it will support both conventions for at least 2 major revisions (thus 2.x and 3.x). We have favored the '.php' extension in the logic, so there will be a very slight penalty for '.class.php'.

###Variable naming
As the world evolves and we hardly come across hungarian notation for variables in online examples and courses, we will be removing it throughout all of Konsolidate.

###Indentation and line wrapping
All of the PHP-closing tags (`?>`) are now gone and the indentation is now decreased by one level, this removes a lot of excessive whitespace which was not really doing anything for readability.
In order to maintain readability now a line width of 120 chararters is used and enforced for comments.

###Whitespaces
In order to comply with common practises, the leading and traling whitespace in function calls array/object accessors has been removed.

###Strings
Strings now use the single quote notation internally, with this we've lost the ability to resolve variables automatically in the string declaration and must now be concatenated explicitly, improving the readability for most developers.

###Examples
The old syntax:
```language-php
<?php

	$sClasses = "path/to/classes";

	$aTier = Array(
		"Core" => "{$sClasses}/konsolidate/core"
	};

	$oK = new Konsolidate( $aTier );
	$oK->call( '/Path/To/Module/method', "foo", "bar" );

?>
```

Will become:
```language-php
<?php

$classPath = 'path/to/classes';

$tier = Array(
	'Core' => $classPath . '/konsolidate/core'
};

$K = new Konsolidate($tier);
$K->call('/Path/To/Module/method', 'foo', 'bar');

//?>  no longer used (omitting it breaks the markdown highlighting ;-) )
```

##Changes between v1 and v2
The compatibility

###Config/INI
The configuration array returned from load no longer merges any section from the ini file with a magical 'default' section, this behavior did not affect the assignment of module variables in any way.
```language-ini
[default]
foo=bar

[example]
bar=baz
```
Now results in `['default'=>['foo'=>'bar'], 'example'=>['bar'=>'baz']]`, instead of
`['default'=>['foo'=>'bar'], 'example'=>['foo'=>'bar', 'bar'=>'baz']]`

One can now load ini files and redirect variable assignment to other modules than '/Config', the following example will assign all settings to `/Foo`.
```language-php
$K->call('/Config/INI/load', 'path/to/file.ini', null, '/Foo')`
```

###Config/XML
When loading an XML file the name of the documentElement no longer becomes a module at the top level by default. The default behavior has been changed to be more in alignment with the `Config/INI` module, which gets all of its variables assign to `/Config/*` by default. In order to have similar behaviour, if a target module is specific (of omitted the default is `/Config`), you have to explicitly disable the target (by providing any value resolving to false-ish, e.g. `null` or `false`).
