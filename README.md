# Does Georgia Power's "Smart Usage" plan change your bill?

[Smart Usage](https://www.georgiapower.com/residential/billing-and-rate-plans/pricing-and-rate-plans/smart-usage.html) is a billing plan for residential GA Power customers.

It's complicated. More complicated than charging a rate per KWh. Under a Smart Usage plan, you're *definitely* billed per KWh, but there are other parts of it. There are peak hours and off-peak hours, which are billed at different rates. There is a "demand" cost, which charges you a rate per KWh during your *highest hour of usage*.

So if I sign up for Smart Usage, will my bill go up? Or will it go down? (Or...will it just stay the same?)

Georgia Power doesn't answer that question. And their limited data dashboard does not make answering the question for yourself any easier.

So I built an app to help me.

## What is this thing

For now it's just a UI that fetches historical power usage data from the Southern Company API. If it's useful to me, I'll add some reporting and email alerts. My main goal was just to get a database with my power usage though, so that's all it does!

## WIP

This is **not** meant for production use or hosting on the Internet. It's a personal app I built to host privately, so I haven't taken too much care to make it secure or well-tested. (It's also super messy and inconsistent throughout!)

Since I'm a Georgia Power customer, it *only* works with Georgia Power (as far as I know). It might work with other Southern Company accounts, though.

Since Southern Company's API is technically private, this could stop working at any point if they change endpoints or data structures or whatever.

## How to use it

It's a Laravel app, so it will work anywhere PHP works. The UI is built with [Filament](https://filamentphp.com). I built it with Postgres in mind, but it would probably work fine with other databases. It doesn't do anything special from a data perspective.

It uses a queue for most interactions with the Southern Co. API, so you'll also need to make sure a queue worker is running, but that's about it.

Once you've got it running, you'll just need to create an account, add your Southern Company credentials (which are encrypted), and start pulling data down.

## Credits

The Southern Company API client is based entirely on [@apearson's southern-company-api](https://github.com/apearson/southern-company-api) package, especially the auth flow. (I never would have figured it out without reading through package and its README.)
