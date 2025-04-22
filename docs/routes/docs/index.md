# Introduction

Statimate is a static site generator for minimalist. It aims to generate static sites with a modest level of tools 
without complex frontend libraries. Statimate provides the following key features to make building static sites simple 
and fast.

* Intuitive routing directory structure.
* Multi-inheritable layout system.
* Easily extensible plugin system.
* Simple build/serve CLI tool which supports hot reloading.
* Built-in Laravel Blade template engine, Markdown parser, and more.
* Built-in plugins to boost productivity, including pagination helper, meta-tag manager, and more.

## Inspiration

Statimate was inspired by [11ty](https://www.11ty.dev/). While 11ty aims to be a simplified SSG, it doesn't go far 
enough due to the unique limitation of the JS ecosystem.

In comparison, statimate is written in PHP, which gives you a head start on content creation and management. While many
people say that you shouldn't use PHP anymore, but PHP has some pretty huge advantages for these purposes.
Regardless of how it's actually used today, PHP has always been expected to be mixed in with HTML. As a result, the 
popular template engines in the PHP ecosystem have also evolved to provide a convenient but safe way to mix PHP and HTML. 

But other languages, including JS, don't just tell you to not mix and match; they don't even provide the ability to do 
so. So what happened?

Using 11ty as an example, they had to create lots of small files like `filename.11tydata.js`, `filename.11tydata.json`, 
`filename.json`, `dirname.11tydata.json`, `...` and put it inside the same folder to pass JS values to the template file, 
which makes to clutter up the directory structure rapidly if you're building a static site of mid-size or more.

Since statimate is written in PHP, we instead inserted some PHP code inside the template file, and it allows us to focus
a little more on content management. 

## Installation

Statimate requires a way to run PHP 8.4 on your computer. You can check whether you have a proper PHP installed by 
running `php -v` in a terminal. If `php` is not found or it reports a version number below 8.4, you will need to install
PHP 8.4 before starting.

* **Windows**: [https://windows.php.net/download](https://windows.php.net/download)
* **macOS**: [https://formulae.brew.sh/formula/php](https://formulae.brew.sh/formula/php)
* **Linux**: Google it 😅

Statimate is distributed via packagist. You will need to download composer to user packagist.

* **Composer**: [https://getcomposer.org/download/](https://getcomposer.org/download/)

Once you have both PHP 8.4 and composer ready, you can install statimate by running the command below.

```bash
composer global require headercat/statimate
```
