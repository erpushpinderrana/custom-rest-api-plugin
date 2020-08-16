# Custom Rest API Plugin
Drupal 9 is around the corner and there are plenty of modules/approaches are available to implement API based architecture such as decoupled or headless architecture. There are tons of documents available on the Web which describe how can we expose API from Drupal even without writing a single line of code. What I have seen so far, most developers have been leveraging JSON API, GraphQL and Rest modules etc. to achieve such kind of implementation. Luckily, I have used all these three modules so far and found them so useful while exposing multiple content API from Drupal. Since we know there is always a need to customize the code at the end to make these API responses as per the consumer request.  In this distribution, I am trying to make a demo ready entity API where developers can easily make changes on the fly with minimal code changes.

# Problem Statement
Recently, I was preparing for a demo and came across a scenario where I needed to show the OOTB Drupal API (JSON) response. I did enable the core JSON  module and showcase the default response for the entities. In addition, I did install the Rest module and shows how easily we expose an API. The only concern in both the cases was that why the final response contains default metadata of an entity such as created, updated, or author name etc. Though it can be achieved through by changing in the request payload in the JSON API case or through Display configuration/ by other means in the REST case, the naming convention of key name or tweaking little format etc. requires some customization through code or a contributed module. To make it better suitable for the end customer, I thought of creating a custom Plugin where a developer has full control over the final JSON format and it can be modified with minimal changes.

# Solution
Out of the above mentioned three modules, I found REST module is more flexible to achieve custom API endpoint and JSON output. Hence, I created a custom module i.e. `custom_rest_api` to expose an entity API. Now, this entity can be any Drupal entity such as Node, Taxonomy Term, User, or Paragraphs.  The API path is 
```
/api/entity/{entity_type}/{entity_id}?_format=json
```

Moreover, I have added the latest Drupal vanilla setup using [Lando](https://docs.lando.dev/config/drupal9.html#getting-started) so that anyone can pull this repository and use it immediately.

# Let's start and follow the below steps to set it up using lando.

## Prerequisites

Install [Lando](https://docs.lando.dev/config/drupal9.html#getting-started)

## Quick Setup
1. Clone this repository and run the below command when you are in the `custom-rest-api-plugin` directory.
```
lando start
```
2. Download the Drupal core and its dependencies using composer.
```
lando composer install
```
3. Get the server URL, database credentials etc. using `lando info` command.
```
lando info
```
4. It will output something like below:
```
[ { service: 'appserver',
    urls:
     [ 'https://localhost:32796',
       'http://localhost:32797',
       'http://drupal9-rest-api.lndo.site/',
       'https://drupal9-rest-api.lndo.site/' ],
    type: 'php',
    healthy: true,
    via: 'apache',
    webroot: '.',
    config: { php: '/Users/pr/.lando/config/drupal9/php.ini' },
    version: '7.3',
    meUser: 'www-data',
    hasCerts: true,
    hostnames: [ 'appserver.drupal9restapi.internal' ] },
  { service: 'database',
    urls: [],
    type: 'mysql',
    healthy: true,
    internal_connection: { host: 'database', port: '3306' },
    external_connection: { host: '127.0.0.1', port: '32798' },
    healthcheck: 'bash -c "[ -f /bitnami/mysql/.mysql_initialized ]"',
    creds: { database: 'drupal9', password: 'drupal9', user: 'drupal9' },
    config: { database: '/Users/pr/.lando/config/drupal9/mysql.cnf' },
    version: '5.7',
    meUser: 'www-data',
    hasCerts: false,
    hostnames: [ 'database.drupal9restapi.internal' ] } ]
```
5. Access the site using any of the above URL and setup the Drupal locally. It will ask for the database credentials that you can get from the above info. For example: I use it using `http://drupal9-rest-api.lndo.site` URL.
6. Enable `Custom RESTful API Web Services` module and `Custom Entity REST API Resource` using `http://drupal9-rest-api.lndo.site/admin/config/services/rest`. Now you may generate sample content using Devel generate module to check the API.

## Entity API Usages

**Node:** Get a node entity.
```
http://drupal9-rest-api.lndo.site/api/entity/node/43?_format=json
```

**Taxonomy:** Get a taxonomy term entity.
```
http://drupal9-rest-api.lndo.site/api/entity/taxonomy_term/4?_format=json
```

**User:** Get a User entity.
```
http://drupal9-rest-api.lndo.site/api/entity/user/1?_format=json
```

**Paragraphs:** Get a Paragraph entity.
```
http://drupal9-rest-api.lndo.site/api/entity/paragraph/1?_format=json
```

## Example: Drupal Entity Manage Display Configurations

Configure Restful formatter with Article Entity Content Type
![Article Entity Restful formatter](https://github.com/erpushpinderrana/files/blob/master/Restful%20Formatters.png)

Node Entity JSON Output
![Article Entity API JSON](https://github.com/erpushpinderrana/files/blob/master/Node_JSON_with_Paragraph.png)

Configure Restful formatter with Tags Entity Taxonomy
![Tags Entity Restful formatter](https://github.com/erpushpinderrana/files/blob/master/Taxonomy%20Restful%20Formatters.png)

Taxonomy Entity JSON Output
![Tags Entity API JSON](https://github.com/erpushpinderrana/files/blob/master/Taxonomy%20Entity%20JSON%20without%20Restful%20Formatters.png)

Configure Restful formatter with User Entity
![User Entity Restful formatter](https://github.com/erpushpinderrana/files/blob/master/User%20Entity%20Restful%20Formatters.png)

User Entity JSON Output
![User Entity API JSON](https://github.com/erpushpinderrana/files/blob/master/User%20Entity%20JSON%20Response.png)

Configure Restful formatter with Paragraph Entity
![Paragraph Entity Restful formatter](https://github.com/erpushpinderrana/files/blob/master/Paragraphs%20Restful%20Formatters.png)

Paragraph Entity JSON Output
![Paragraph Entity API JSON](https://github.com/erpushpinderrana/files/blob/master/Paragraph%20Entity%20JSON.png)



