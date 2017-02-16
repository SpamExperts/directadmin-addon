[![Code Climate](https://codeclimate.com/github/SpamExperts/directadmin-addon/badges/gpa.svg)](https://codeclimate.com/github/SpamExperts/directadmin-addon) [![Issue Count](https://codeclimate.com/github/SpamExperts/directadmin-addon/badges/issue_count.svg)](https://codeclimate.com/github/SpamExperts/directadmin-addon)

# DirectAdmin addon
An add-on for [DirectAdmin control panel](http://directadmin.com) providing integration with SpamExperts services.

## Documentation
Documentation can be found [here](https://my.spamexperts.com/kb/50/DirectAdmin-addon.html "SpamExperts' Knowledge base")

### Using DirectAdmin login keys

To secure the add-on setup it's recommended to use [DirectAdmin Login Keys](https://www.directadmin.com/features.php?id=1298) for all communications with DirectAdmin API. In order to use a Login Key the following steps have to be performed.

#### Login Keys must be enabled

Check your `directadmin.conf` and make sure that Login Keys are enabled:
```
login_keys=1
```
Also make sure that the user that you're going to use for API communications does not have that setting overriden. F.i. in that's the `admin`, the `login_keys` value in `/usr/local/directadmin/data/users/admin/user.conf` should be `1` or `On`.

If you are making chnages to the configuration file(s) don't forget to reload the `directadmin` service (e.g. `service directadmin restart`).

#### Login Key creation

1. Log in as the user that you're going to use for the DirectAdmin API communication (it's `admin` in our case);
1. Go to `http://your.directadmin.host:2222/CMD_LOGIN_KEYS`
1. Create a new Login Key
    1.1 Pick a meaning Key Name
    1.1 Use hard-to-guess Key Value 
    1.1 Allow the following commands:
        1.1.1 `CMD_API_ALL_USER_USAGE`
        1.1.1 `CMD_API_DNS_ADMIN`
        1.1.1 `CMD_API_DNS_MX`
        1.1.1 `CMD_API_SHOW_USER_USAGE`
        1.1.1 `CMD_API_SYSTEM_INFO`
    1.1 Allow your localhost IP address only
    
#### Login Key utilization
    
Once the key is ready use it as the `password` configuration directive value in the `directadminapi.conf` file.

#### Troubleshooting

If you experience issues when using a Login Key it may be useful to turn DirectAdmin into a debug mode and troubleshoot the issue. More info on this can be found [here](https://help.directadmin.com/item.php?id=293).