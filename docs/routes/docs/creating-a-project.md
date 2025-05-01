---
title: Docs > Creating a Project
---
# Creating a Project

If you haven't already installed statimate, run `composer global require headercat/statimate` to install.
For more information, check out the requirements and installation instructions in the [introduction](/docs/). 

## 1. Create a project directory

Create a directory for your project using `mkdir` command:
```bash
mkdir your-project
```

Now move into the directory you created with `cd` command:
```bash
cd your-project
```

## 2. Initialize statimate

Statimate CLI tool provides a command to generate and initialize the required files needed for your project:
```bash
statimate init
```

This will create about four files – `statimate.config.php`, `composer.json`, `routes/index.blade.php`, 
`routes/#layout.blade.php`, and automatically install dependencies via `composer install`.

## 3. Run statimate

You are now ready to start your first statimate project. Open a development server that supports hot-reloading with:
```bash
statimate serve
```

The command above opens the development server on port 8080. If you're already using the port, you can also specify your
own port number by:
```bash
statimate serve 8081
```

## 4. That's it!

You finish creating a new project. Now, read the articles below to enrich your use of statimate. It shouldn't take too 
long to read.

* TBD
