=== Cachify ===
Contributors: sergej.mueller
Tags: performance,caching,cache
Donate link: http://flattr.com/profile/sergej.mueller
Requires at least: 3.0
Tested up to: 3.3
Stable tag: trunk

Simple und effiziente Cache-Lösung für WordPress.


== Description ==
= Unkompliziert und ausbaufähig =
Cachify optimiert die Ladezeit der Blogseiten, indem Seiteninhalte in statischer Form in der Datenbank abgelegt und beim Seitenaufruf direkt ausgegeben werden. Dabei wird die Anzahl der DB-Anfragen und PHP-Anweisungen reduziert.

= Einige Stärken =
* NEU: APC-Support für perfekten Cache
* Adminbar-Schaltfläche "Cache leeren"
* Einfache Installation: Aktivieren, fertig
* Übersichtliche Optionsseite mit verknüpfter Online-Hilfe
* Optionale Komprimierung der HTML-Ausgabe
* Ausnahmelisten für PostIDs und User Agents
* Bis zu 80 % weniger DB-Anfragen
* Bis zu 60 % schnellere Ausführungszeiten
* Manueller und automatischer Cache-Reset
* Ausgabe der "davor, danach" Informationen im Quelltext

= Wichtige Information =
Vor der Inbetriebnahme des Plugins ist die [Dokumentation](http://playground.ebiene.de/cachify-wordpress-cache/ "Cachify WordPress Cache") durchzulesen, um eine inkorrekte Funktionsweise und negative Auswirkungen des Cache-Tools zu vermeiden!

= Weiterführende Links =
* [Google+](https://plus.google.com/110569673423509816572 "Google+")
* [Other plugins](http://wordpress.org/extend/plugins/profile/sergejmueller "Other plugins")


== Changelog ==

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

1. Cachify Optionsseite


== Installation ==
1. *Cachify* installieren
1. [Dokumentation](http://playground.ebiene.de/cachify-wordpress-cache/ "Cachify WordPress Cache") beachten
1. Einstellungen vornehmen