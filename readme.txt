=== Cachify ===
Contributors: sergej.mueller
Tags: apc, cache, caching, performance
Donate link: http://flattr.com/profile/sergej.mueller
Requires at least: 3.1
Tested up to: 3.4
Stable tag: trunk



Turbo für WordPress. Smarte, aber effiziente Cache-Lösung für WordPress. Mit der Konzentration aufs Wesentliche.



== Description ==

= Unkompliziert und ausbaufähig =
*Cachify* optimiert Ladezeit der Blogseiten, indem Seiteninhalte in statischer Form wahlweise in der Datenbank, auf der Festplatte des Webservers oder im APC (Alternative PHP Cache) abgelegt und beim Seitenaufruf ohne Umwege ausgegeben werden. Die Anzahl der DB-Anfragen und PHP-Anweisungen reduziert sich je nach Methode um Faktor 10.

= Stärken =
* Speicherungsmethoden: DB, HDD und APC
* "Cache leeren" in der Admin Bar
* Inline- und Online-Handbuch
* Optionale Komprimierung der HTML-Ausgabe
* Ausnahmelisten für Beiträge und User Agents
* Manueller und automatischer Cache-Reset
* Ausgabe der "davor, danach" Informationen im Quelltext
* Verständliche Oberfläche zum Sofortstart
* Automatisches Management des Cache-Bestandes
* Cache-Belegung auf dem Dashboard

= Information =
* [Offizielle Homepage](http://cachify.de "Cachify WordPress Cache")
* [Online-Dokumentation](http://playground.ebiene.de/cachify-wordpress-cache/ "Cachify Online-Doku")

= Autor =
* [Google+](https://plus.google.com/110569673423509816572 "Google+")
* [Portfolio](http://ebiene.de "Portfolio")



== Changelog ==

= 2.0 =
* Überarbeitung der GUI
* Source Code-Modularisierung
* Cache-Größe auf dem Dashboard
* Festplatte als Ablageort für Cache
* Mindestanforderungen: WordPress 3.1
* Produktseite online: http://cachify.de
* Cache-Neuaufbau bei Kommentarstatusänderungen
* APC-Anforderungen: APC 3.0.0, empfohlen 3.1.4
* Optional: Kein Cache für kommentierende Nutzer
* Schnellübersicht der Optionen als Inline-Hilfe

= 1.5.1 =
* `zlib.output_compression = Off` für Apache Webserver

= 1.5 =
* Überarbeitung des Regexp für HTML-Minify
* Reduzierung des Toolbar-Buttons auf das Icon
* Formatierung und Kommentierung des Quelltextes

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