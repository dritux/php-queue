# Starting PubSub Example

### Install dependencies

```
$ composer install
```

### Credentials
```
$ export GOOGLE_APPLICATION_CREDENTIALS=/{path_to}/config/credentials.json
```

### Starting publisher

```
$ cd {path}/examples/pubsub/
$ php publish.php
```


### (optional) Check queue create

```
$ gcloud pubsub topics list
$ gcloud pubsub subscriptions list

```
