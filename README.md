# Dr Queue

Queue drivers for PHP

### Requirements
- PHP Extension bcmath
- PHP Extension curl
- PHP Extension amqp

### Available adapters
- Amazon SQS
- AMQP
- Google PubSub
- InMemory Mock Tests

### Installation
Then run the command:
```
$ composer require dritux/php-queue
```
### Usage
See the examples directory content.

### Credentials
```
export GOOGLE_APPLICATION_CREDENTIALS=/{path_to}/config/{credentials}.json
```

### Commands
```
$ gcloud pubsub topics create php-backend
$ gcloud pubsub subscriptions create sale-subscription --topic php-backend

$ gcloud pubsub topics list
$ gcloud pubsub subscriptions list
$ gcloud projects list

$ gcloud pubsub subscriptions pull sale-subscription
$ gcloud pubsub subscriptions pull sale-subscription --limit 10
$ gcloud pubsub subscriptions delete mySubscription
$ gcloud pubsub topics delete myTopic
```

### References
[Docs](https://cloud.google.com/pubsub/docs/)  
[Getting Started](https://cloud.google.com/php/getting-started/using-pub-sub)  
[Publisher](https://cloud.google.com/pubsub/docs/publisher)  
[Client Library Reference](https://cloud.google.com/pubsub/docs/reference/libraries)  
[Writing and responding to pubsub messages](https://cloud.google.com/appengine/docs/flexible/php/writing-and-responding-to-pub-sub-messages)  
