# Gatekeeper

Gatekeeper is a package to manage Feature Flagging within a Laravel project.

It was inspired by the [AdEspresso Feature Flag Bundle](https://github.com/adespresso/FeatureBundle) and various other Laravel sFeature Flag spin-offs.

## What is Feature Flagging?

Feature Flagging is basically a way to **have full control on the activation of a feature** in your applications.

Let's make a couple of examples to give you an idea:

* you just finished to work on the latest feature and you want to push it, but the marketing team wants you to deploy it in a second moment;
* the new killer-feature is ready, but you want to enable it only for a specific set of users;

With Gatekeeper, you can:

* easily **define new features** in your application;
* **enable/disable features** globally;
* **enable/disable features for specific users**, or **for whatever you want**;

There are many things to know about feature toggling: take a look to [this great article](http://martinfowler.com/articles/feature-toggles.html) for more info. It's a really nice and useful lecture.

## Install

You can install Gatekeeper with Composer.

``` bash
$ composer require philspil66/gatekeeper
```

After that, you need to **add the `FeatureServiceProvider` to the `app.php` config file**.

```php
...
Gatekeeper\Provider\FeatureServiceProvider::class,
...
```

Now you have to **run migrations**, to add the tables Laravel-Feature needs.

```bash
$ php artisan migrate
```

... and you're good to go!

### Facade

If you want, you can also **add the `Feature` facade** to the `aliases` array in the `app.php` config file.

```php
...
'Feature' => \Gatekeeper\Facade\Feature::class,
...
```

If you don't like Facades, **inject the `FeatureManager`** class wherever you want!

### Config File

By default, you can immediately use Gatekeeper. However, if you want to tweak some settings, feel free to **publish the config file** with

```bash
$ php artisan vendor:publish --provider="Gatekeeper\Provider\FeatureServiceProvider"
```

## Basic Usage

There are two ways you can use features: working with them **globally** or **specifically for a specific entity**.

### Globally Enabled/Disabled Features

#### Declare a New Feature

Let's say you have a new feature that you want to keep hidden until a certain moment. We will call it "new_super_feature". Let's **add it to our application**:

```php
Gatekeeper::add('new_super_feature', false);
```

Easy, huh? As you can imagine, **the first argument is the feature name**. **The second is a boolean we specify to define the current status** of the feature.

* `true` stands for **the feature is enabled for everyone**;
* `false` stands for **the feature is hidden, no one can use it/see it**;

And that's all.

#### Check if a Feature is Enabled

Now, let's imagine a better context for our example. We're building a CMS, and our "new_super_feature" is used to... clean our HTML code. Let's assume we have a controller like this one.

```php
class CMSController extends Controller {
    public function getPage($pageSlug) {
        
        // here we are getting our page code from some service
        $content = PageService::getContentBySlug($pageSlug);
        
        // here we are showing our page code
        return view('layout.pages', compact('content'));
    }
}
```

Now, we want to deploy the new service, but **we don't want to make it available for users**, because the marketing team asked us to release it the next week. LaravelFeature helps us with this:

```php
class CMSController extends Controller {
    public function getPage($pageSlug) {
        
        // here we are getting our page code from some service
        $content = PageService::getContentBySlug($pageSlug);
        
        // feature flagging here!
        if(Gatekeeper::isEnabled('new_super_feature')) {
            $content = PageCleanerService::clean($content);
        }
        
        // here we are showing our page code
        return view('layout.pages', compact('content'));
    }
}
```

Ta-dah! Now, **the specific service code will be executed only if the "new_super_feature" feature is enabled**.

#### Change a Feature Activation Status

Obviously, using the `Feature` class we can easily **toggle the feature activation status**.

```php
// release the feature!
Gatekeeper::enable('new_super_feature');

// hide the feature!
Gatekeeper::disable('new_super_feature');
```

#### Remove a Feature

Even if it's not so used, you can also **delete a feature** easily with

```php
Gatekeeper::remove('new_super_feature');
```

Warning: *be sure about what you do. If you remove a feature from the system, you will stumble upon exceptions if checks for the deleted features are still present in the codebase.*

#### Work with Views

I really love blade directives, they help me writing more elegant code. I prepared **a custom blade directive, `@feature`**:

```php
<div>This is an example template div. Always visible.</div>

@feature('my_awesome_feature')
    <p>This paragraph will be visible only if "my_awesome_feature" is enabled!</p>
@endfeature

<div>This is another example template div. Always visible too.</div>
```

A really nice shortcut!

### Enable/Disable Features for Specific Users/Entities

Even if the previous things we saw are useful, LaravelFeature **is not just about pushing the on/off button on a feature**. Sometimes, business necessities require more flexibility. Think about a [**Canary Release**](http://martinfowler.com/bliki/CanaryRelease.html): we want to rollout a feature only to specific users. Or, maybe, just for one tester user.

#### Enable Features Management for Specific Users

LaravelFeature makes this possible, and also easier just as **adding a trait to our `User` class**.

In fact, all you need to do is to: 

* **add the `Gatekeeper\Featurable\Featurable` trait** to the `User` class;
* let the same class **implement the `FeaturableInterface` interface**;

```php
...

class User extends Authenticatable implements FeaturableInterface
{
    use Notifiable, Featurable;
    
...
```

Nothing more! Gatekeeper now already knows what to do.

#### Status Priority

*Please keep in mind that all you're going to read from now is not valid if a feature is already enabled globally. To activate a feature for specific users, you first need to disable it.*

Laravel-Feature **first checks if the feature is enabled globally, then it goes down at entity-level**.

#### Enable/Disable a Feature for a Specific User

```php
$user = Auth::user();

// now, the feature "my.feature" is enabled ONLY for $user!
Gatekeeper::enableFor('my.feature', $user);

// now, the feature "my.feature" is disabled for $user!
Gatekeeper::disableFor('my.feature', $user);

```

#### Check if a Feature is Enabled for a Specific User

```php
$user = Auth::user();

if(Gatekeeper::isEnabledFor('my.feature', $user)) {
    
    // do amazing things!
    
}
```

#### Other Notes

Gatekeeper also provides a Blade directive to check if a feature is enabled for a specific user. You can use the `@featurefor` blade tags:
```php
@featurefor('my.feature', $user)
    
    // do $user related things here!
    
@endfeaturefor
```

## Advanced Things

Ok, now that we got the basics, let's raise the bar!

### Enable Features Management for Other Entities

As I told before, you can easily add features management for Users just by using the `Featurable` trait and implementing the `FeaturableInterface` in the User model. However, when structuring the relationships, I decided to implement a **many-to-many polymorphic relationship**. This means that you can **add feature management to any model**!

Let's make an example: imagine that **you have a `Role` model** you use to implement a basic roles systems for your users. This because you have admins and normal users. 

So, **you rolled out the amazing killer feature but you want to enable it only for admins**. How to do this? Easy. Recap:

* add the `Featurable` trait to the `Role` model;
* be sure the `Role` model implements the `FeaturableInterface`;

Let's think the role-user relationship as one-to-many one.

You will probably have a `role()` method on your `User` class, right? Good. You already know the rest:

```php
// $role is the admin role!
$role = Auth::user()->role;

...

Gatekeeper::enableFor('my.feature', $role);

...

if(Gatekeeper::isEnabledFor('my.feature', $role)) {

    // this code will be executed only if the user is an admin!
    
}
```


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.


## Credits

* [Phil Spilsbury](https://github.com/philspil66)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


