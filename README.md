This is the code base for the drupal mail module using Envoke

## Reference
- [Envoke API](https://support.envoke.com/en/collections/545624-api)

## Usage
- Add
    ```
        {
            "type": "vcs",
            "url": "ssh://git@git.affinitybridge.com:22222/affinitybridge/envoke.git"
        }
    ```
    to composer.json
- Run `composer require affinitybridge/envoke` to install the module

## Email template
- Add `envoke-mail.html.twig` to the project theme to override the default Envoke mail template

## Envoke Service
- Envoke service provides `sendEmail` and `insertContactIfNotExist` that supports sending email through Envoke APIs and update Envoke contacts

## Composer.json
- Currently the type is `drupal-module` that will place the installed module in the `/module/contrib/` directory
