SocialContest
=============

Azure-based application to work with SocialCloud to run a Twitter-based contest at a conference
or other event using hashtags or keywords.

REQUIREMENTS
============
* PHP 5.5
* Azure Service Bus subscription & queues -- see config for Social Cloud and in src/Config
* Azure Table - only one table is needed
* Azure blob
* Social Cloud: https://github.com/openlab/socialcloud-emitters https://github.com/openlab/socialcloud-socketio-server


SOCIAL CLOUD
============
Running on http://mycontestnode.cloudapp.net, can login with mycontest.key

To start up the emitters ssh:

    > cd /home/azureuser/socialcloud/sc-emitters
    > node app.js

OR to run it in the background using forever script

    > cd /home/azureuser/socialcloud/sc-emitters
    > forever -o out.log -e err.log app.js

SOCIAL CLOUD EMITTERS - config.js
=================================
Sample config.js:

    module.exports = {
            azureNamespace: "mycontest",
            azureAccessKey: "myAzureAccessKey",
            messageTopic: "messagetopic",
            configTopic: "configtopic",
            wcSubName: 'wc-sub',
            configSubName: 'config-sub',
            alertSubName: 'alertSub',
            maxWords: 20,
            twitterTrack: "myhashtag",
            consumer_key: 'myConsumerKey',
            consumer_secret: 'myConsumerSecret',
            access_token_key: 'myAccessTokenKey',
            access_token_secret: 'myAccessTokenSecret',
            instagramKey: 'myInstagramKey',
            instagramSecret: 'myInstagramSecret',
            mongoPath: 'mongodb://user:pass@mymongoserver.mongolab.com:27748/mymongoinstance',
            sendgridUser: 'mysendgriduser',
            sendgridKey: 'mysendgridkey',
            twillioSid: 'myTwilioSid',
            twillioToken: 'myTwilioToken',
            twillioFromNumber: '5555555555',
            pusherAppId: 'myPusherAppId',
            pusherKey: 'myPusherKey',
            pusherSecret: 'myPusherSecret',
            amsPushUrl: '',
            amsApplicationKey: '',
            newRelicKey: "myNewRelicKey",
            foursquareKeys: {
                    'secrets': {
                            'clientId': 'myClientId',
                            'clientSecret': 'myClientSecret',
                            'redirectUrl': 'http://localhost:3000/callback'

                    }
            }

    };

SOCIAL CLOUD TWITTER EMMITER
============================
We made two small changes to twitterEmitter.js, to specify the hash tags to look for and to
send the media entity url along with the original tweet

    diff --git a/emitters/twitterEmitter.js b/emitters/twitterEmitter.js
    index 5e59cf2..351bf3f 100644
    --- a/emitters/twitterEmitter.js
    +++ b/emitters/twitterEmitter.js
    @@ -19,6 +19,10 @@ var listener = null;

     module.exports.stream = function(service) {
       settingsRepo.getByKeyWithDefault('twitterSearchTerm', 'Microsoft', function(obj) {
    +    // OAM override term here
    +    // https://dev.twitter.com/docs/streaming-apis/parameters#track
    +//    obj.value = "zendcon azure";
    +    obj.value = "musketeersmetest";
         theTrack = obj.value;
         twit.stream('statuses/filter', {
           track: theTrack
    @@ -85,7 +89,13 @@ function handleData(data, service) {
         dateTime: data.created_at
         //}
       };
    -  //console.log(msg);
    +
    +  // OAM If we have media with the tweet send it with our tweet message
    +  // so we don't have to match them up later.
    +  if (data.entities.media) {
    +    msg.media_url = data.entities.media[0].media_url;
    +  }
    +  console.log(msg);
       socialMessage.add(msg, function(err, obj) {
         if (err)
           console.log("Error saving to mongo", err);
    @@ -122,13 +132,14 @@ function handleData(data, service) {
     }


CONTEST FRONT END
=================
Running on http://mycontest.cloudapp.net, can login with mycontest.key

Code is in `/var/www/contest`.

/bin SCRIPTS
============
The following are daemons started up by running bin/startup.sh.

- `incoming.php` - processes subscription messages from socialcloud, creates entries.
- `to_approve.php` - watches to-approve queue for messages to change status on an entry to
'Approved'.
- `to_denied.php` - watches to-deny queue for messages to change status on an entry to 'Denied'.
- `to_incoming.php` - watches to-incoming queue for messages to change status on an entry to 'New'.
- `to_winner.php` - watches to-incoming queue for messages to change status on an entry to 'New'.

There is one cron script that needs to run every 10 minutes. Based on the start, end,
daily start, daily end, and interval settings in config.php, it will pick a winner from
approved entries.

- `winner.php` - select a winner.

VAGRANT DEVELOPMENT
===================
After setting up all servers on Azure, install VirtualBox and Vagrant on your system,
then go to the directory containing this code on your local development box and issue `vagrant
up` on the command line.

You can access it at `http://192.168.56.101/` or set up a custom name in `/etc/hosts`.

After the deployment runs, issue `vagrant ssh` and configure your server. You'll need to find the
 VirtualHost directory where it defines the web root and add `AllowOverrides All` to that
 directory.

Then `cd /var/www/contest` and run `composer update --prefer-dist`. Copy
`src/Config/config-sample.php` to `config.php`, add your keys to the configuration,
and it should be ready to run.

You can generate a password for the Admin area by running

`php -r 'echo password_hash("my really secure password but not this one", PASSWORD_DEFAULT) . "\n";`
