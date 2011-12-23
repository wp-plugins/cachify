=== Cachify ===
Contributors: sergej.mueller
Tags: apc, cache, caching, performance
Donate link: http://flattr.com/profile/sergej.mueller
Requires at least: 3.0
Tested up to: 3.3
Stable tag: trunk



Smarte, aber effiziente Cache-Lösung für WordPress. Mit der Konzentration aufs Wesentliche. Empfehlenswert für CMS-Seiten.



== Description ==

= Unkompliziert und ausbaufähig =
*Cachify* optimiert Ladezeit der Blogseiten, indem Seiteninhalte in statischer Form in der Datenbank abgelegt und beim Seitenaufruf direkt ausgegeben werden. Die Anzahl der DB-Anfragen und PHP-Anweisungen reduziert sich um ein Vielfaches.

= Stärken =
* Unterstützung für APC (Alternative PHP Cache)
* "Cache leeren" in der Admin Bar
* Trviale Installation begleitet vom Online-Handbuch
* Optionale Komprimierung der HTML-Ausgabe
* Ausnahmelisten für Beiträge und User Agents
* Bis zu 80 % weniger DB-Anfragen
* Bis zu 60 % schnellere Ausführungszeiten
* Manueller und automatischer Cache-Reset
* Ausgabe der "davor, danach" Informationen im Quelltext

= Dokumentation =
* [Cachify WordPress Cache](http://playground.ebiene.de/cachify-wordpress-cache/ "Cachify WordPress Cache")

= Autor =
* [Google+](https://plus.google.com/110569673423509816572 "Google+")
* [Portfolio](http://ebiene.de "Portfolio")



== Changelog ==

= 1.4 =
* Xmas Edition

= 1.3 =
* Unterstützung für APC (Alternative PHP Cache)
* Umpositionierung des Admin Bar Buttons

= 1.2.1 =
* Icon für die "Cache leeren" Schaltfläche in der Admin Bar

= 1.2 =
* Schaltfläche "Cache leeren" in der Adminbar (ab WordPress 3.1)
* `flush_cache` auf public gesetzt, um von [wpSEO](http://wpseo.de "WordPress SEO Plugin") ansprechen zu können
* Ausführliche Tests unter WordPress 3.3

= 1.1 =
* Interne Prüfung auf fehlerhafte Cache-Generierung
* Anpassungen an der Code-Struktur
* Entfernung der Inline-Hilfe
* Verknüpfung der Online-Hilfe mit Optionen

= 1.0 =
* Leerung des Cache beim Aktualisieren von statischen Seiten
* Seite mit Plugin-Einstellungen
* Inline-Dokumentation in der Optionsseite
* Ausschluss von Passwort-geschützten Seiten
* WordPress 3.2 Support
* Unterstützung der WordPress Multisite Blogs
* Umstellung auf den template_redirect-Hook (Plugin-Kompatibilität)
* Interne Code-Bereinigung

= 0.9.2 =
* HTML-Kompression
* Flattr-Link

= 0.9.1 =
* Cache-Reset bei geplanten Beiträgen
* Unterstützung für das Carrington-Mobile Theme

= 0.9 =
* Workaround für Redirects

= 0.8 =
* Blacklist für PostIDs
* Blacklist für UserAgents
* Ausnahme für WP Touch
* Ausgabe des Zeitpunktes der Generierung
* Umbenennung der Konstanten

= 0.7 =
* Ausgabe des Speicherverbrauchs

= 0.6 =
* Live auf wordpress.org



== Screenshots ==

1. Cachify Optionen