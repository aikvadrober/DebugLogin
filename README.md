# AiKvadrober DebugLogin module

One GET request logs you into a Magento 2 storefront as any customer. No passwords, no passkeys, no password-reset emails, no CAPTCHA guessing. Copy it into `app/code`, hit the URL, you are in - as admin, as QA, as the customer who reported the bug.

## Why

Testing a customer-facing bug used to mean: reset the password, dig the reset link out of Mailtrap, guess the CAPTCHA, repeat for every store view. That is ten minutes of ceremony for one page view. `AiKvadrober_DebugLogin` replaces all of it with a single link you keep in your notes.

- Zero configuration: no admin settings, no customer attributes, no schema changes.
- Zero footprint: one controller, two XML files, no cron, no observers, no queues.
- Safe by default: hard-coded guards make it a silent 404 anywhere except a local dev host in developer mode.

## Install

Drop the module into a local Magento project:

```bash
# from the project root
git clone <this-repo> app/code/AiKvadrober/DebugLogin
bin/magento module:enable AiKvadrober_DebugLogin
bin/magento setup:upgrade
```

Requires `Magento_Customer` (declared in [`etc/module.xml`](etc/module.xml)). No composer dependencies beyond core.

## Usage

Login as a customer by email or by ID:

```
/debuglogin/index/index?email=a@b.c
/debuglogin/index/index?id=15
```

Land wherever the test needs you:

```
/debuglogin/index/index?email=a@b.c&redirect=customer/address
```

| Parameter  | Meaning                                                                                          |
|------------|--------------------------------------------------------------------------------------------------|
| `email`    | Customer email, resolved on the current website ([`Controller/Index/Index.php`](Controller/Index/Index.php)) |
| `id`       | Customer entity ID, takes precedence over `email`                                                 |
| `redirect` | Frontend path after login, validated against `#^[a-z0-9_\-/]+$#i`, defaults to `customer/account` |

An existing session is logged out first, then the customer is logged in and the session ID is regenerated.

## Safety model

The controller bails out to a `noroute` forward unless **both** guards pass:

- Application mode is `developer` (`Magento\Framework\App\State::MODE_DEVELOPER`).
- The store base URL host matches `/(^localhost$|\.ddev\.site$|\.test$|\.local$)/`.

Deploy it to staging or production and the URL is a plain 404. Still, this is a debug tool: keep it out of production trees anyway.

## Files

- [`Controller/Index/Index.php`](Controller/Index/Index.php) - the whole module: guards, lookup, login, redirect
- [`etc/frontend/routes.xml`](etc/frontend/routes.xml) - `debuglogin` front name
- [`etc/module.xml`](etc/module.xml) - module declaration, sequence `Magento_Customer`
- [`registration.php`](registration.php) - component registrar
