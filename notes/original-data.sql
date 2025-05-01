# ************************************************************
# Sequel Ace SQL dump
# Version 20080
#
# https://sequel-ace.com/
# https://github.com/Sequel-Ace/Sequel-Ace
#
# Host: 127.0.0.1 (MySQL 11.6.2-MariaDB)
# Database: act
# Generation Time: 2025-02-24 18:00:02 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
SET NAMES utf8mb4;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE='NO_AUTO_VALUE_ON_ZERO', SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table contacts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `contacts`;

CREATE TABLE `contacts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `from_name` longtext DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `wa_phone` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  `date_sent` char(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;

INSERT INTO `contacts` (`id`, `from_name`, `from_email`, `wa_phone`, `subject`, `body`, `date_sent`)
VALUES
	(1,'mike','1@3.com',984522104,'subject','body','2025-02-01 13:18:15');

/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table fixr_webhook_responses
# ------------------------------------------------------------

DROP TABLE IF EXISTS `fixr_webhook_responses`;

CREATE TABLE `fixr_webhook_responses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `received_at` timestamp NULL DEFAULT current_timestamp(),
  `response` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table gallery
# ------------------------------------------------------------

DROP TABLE IF EXISTS `gallery`;

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `show_id` int(11) NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `show_id` (`show_id`),
  CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`show_id`) REFERENCES `shows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `gallery` WRITE;
/*!40000 ALTER TABLE `gallery` DISABLE KEYS */;

INSERT INTO `gallery` (`id`, `show_id`, `image`)
VALUES
	(1,3,'673f70e6f28b8.jpeg'),
	(2,3,'673f70e6f2913.jpeg'),
	(3,3,'673f70e6f2977.jpeg'),
	(4,3,'673f70e6f29d7.jpeg'),
	(5,4,'673f70e6f2ba3.jpeg'),
	(6,4,'673f70e6f2bfa.jpeg'),
	(7,4,'673f70e6f2c4c.jpeg'),
	(8,4,'673f70e6f2cad.jpeg'),
	(9,5,'673f70e6f22b5.jpg'),
	(10,5,'673f70e6f2313.jpg'),
	(11,5,'673f70e6f2371.jpg'),
	(12,5,'673f70e6f23be.jpg'),
	(13,7,'673f70e6f309e.jpeg'),
	(14,7,'673f70e6f30fc.jpeg'),
	(15,7,'673f70e6f316a.jpeg'),
	(16,7,'673f70e6f31c8.jpeg'),
	(17,10,'673f70e6f1813.png'),
	(18,10,'673f70e6f185d.png'),
	(19,10,'673f70e6f18a4.png'),
	(20,10,'673f70e6f18ed.png'),
	(21,12,'673f70e6f256c.jpeg'),
	(22,12,'673f70e6f25cb.jpeg'),
	(23,12,'673f70e6f2622.jpeg'),
	(24,12,'673f70e6f267b.jpeg'),
	(25,13,'673f70e6f1240.png'),
	(26,13,'673f70e6f12f7.png'),
	(27,13,'673f70e6f1383.png'),
	(28,13,'673f70e6f1414.png'),
	(29,14,'673f70e6f338c.jpeg'),
	(30,14,'673f70e6f33e0.jpeg'),
	(31,14,'673f70e6f3430.jpeg'),
	(32,14,'673f70e6f3484.jpeg'),
	(33,15,'673f70e6f2e0f.jpeg'),
	(34,15,'673f70e6f2e66.jpeg'),
	(35,15,'673f70e6f2eb7.jpeg'),
	(36,15,'673f70e6f2f01.jpeg'),
	(37,17,'673f70e6f1d44.jpg'),
	(38,17,'673f70e6f1d9e.jpg'),
	(39,17,'673f70e6f1dff.jpg'),
	(40,17,'673f70e6f1e57.jpg'),
	(41,19,'673f70e6f200b.png'),
	(42,19,'673f70e6f206e.png'),
	(43,19,'673f70e6f20cc.png'),
	(44,19,'673f70e6f212f.png');

/*!40000 ALTER TABLE `gallery` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table navigation
# ------------------------------------------------------------

DROP TABLE IF EXISTS `navigation`;

CREATE TABLE `navigation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `visible` tinyint(1) DEFAULT 1,
  `editable` tinyint(1) DEFAULT 1,
  `reposition` tinyint(1) DEFAULT 1,
  `can_delete` tinyint(1) DEFAULT 1,
  `contents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `parent` int(11) DEFAULT 0,
  `external` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `navigation` WRITE;
/*!40000 ALTER TABLE `navigation` DISABLE KEYS */;

INSERT INTO `navigation` (`id`, `label`, `path`, `sort_order`, `visible`, `editable`, `reposition`, `can_delete`, `contents`, `parent`, `external`)
VALUES
	(1,'Donate','',4,1,1,1,1,'<div class=\"bg-primary\"><h6>Donate to ACT and become an Angel today!</h6><div>Did you know that our ticket sales only cover 70% of our operating costs to bring you our six show season? Bar sales and 50/50 ticket sales help, but your contribution as an ACT Angel will allow us to keep bringing you our great entertainment and continue to improve our theater for your enjoyment.</div><div><br></div><div>We\'ll thank you for your donation with the following recognition and benefits:</div><div><br></div><div><b>ACT Angel&mdash;$100</b></div><div><ul><li>Your name listed in every program, on our website, and on our in-theater signage</li><li>Invitation for two to our annual Angel Appreciation Event</li><li>Two drink and two snack coupons per show ($60 value)</li></ul><div><b>ACT Archangel&mdash;$250</b></div></div><div><ul><li>Your name listed in every program, on our website, and on our in-theater signage</li><li>Invitation for two to our annual Angel Appreciation Event</li><li>Two drink and two snack coupons per show ($60 value)</li><li>Access to reserve seating requested in advance for your entire party</li></ul><div><b>ACT Guardian Angel&mdash;$500</b></div></div><div><ul><li>Your name listed in every program, on our website, and on our in-theater signage</li><li>Invitation for two to our annual Angel Appreciation Event</li><li>Four drink and two snack coupons per show ($108 value)</li><li>Access to reserve seating requested in advance for your entire party</li><li>One flex ticket ($90 value)</li></ul><div><b>ACT Seraphim and Producers&mdash;$1000+</b></div></div><div><div><ul><li>Your name listed in every program, on our website, and on our in-theater signage</li><li>Invitation for two to our annual Angel Appreciation Event</li><li>Access to reserve seating requested in advance for your entire party</li><li>Unlimited drinks/snacks for two people at each show (approximately $150 value)</li><li>Two flex tickets ($180 value)</li><li>Producer/Sponsor credit for one show of your choice to be recognized each night of that show\'s performance and on marketing materials</li></ul></div></div><h6>Donate to ACT and become an Angel today!</h6><div>Did you know that our ticket sales only cover 70% of our operating costs to bring you our six show season? Bar sales and 50/50 ticket sales help, but your contribution as an ACT Angel will allow us to keep bringing you our great entertainment and continue to improve our theater for your enjoyment.</div><div><br></div><div>We\'ll thank you for your donation with the following recognition and benefits:</div><div><br></div><div><b>ACT Angel&mdash;$100</b></div><div><ul><li>Your name listed in every program, on our website, and on our in-theater signage</li><li>Invitation for two to our annual Angel Appreciation Event</li><li>Two drink and two snack coupons per show ($60 value)</li></ul><div><b>ACT Archangel&mdash;$250</b></div></div><div><ul><li>Your name listed in every program, on our website, and on our in-theater signage</li><li>Invitation for two to our annual Angel Appreciation Event</li><li>Two drink and two snack coupons per show ($60 value)</li><li>Access to reserve seating requested in advance for your entire party</li></ul><div><b>ACT Guardian Angel&mdash;$500</b></div></div><div><ul><li>Your name listed in every program, on our website, and on our in-theater signage</li><li>Invitation for two to our annual Angel Appreciation Event</li><li>Four drink and two snack coupons per show ($108 value)</li><li>Access to reserve seating requested in advance for your entire party</li><li>One flex ticket ($90 value)</li></ul><div><b>ACT Seraphim and Producers&mdash;$1000+</b></div></div><div><div><ul><li>Your name listed in every program, on our website, and on our in-theater signage</li><li>Invitation for two to our annual Angel Appreciation Event</li><li>Access to reserve seating requested in advance for your entire party</li><li>Unlimited drinks/snacks for two people at each show (approximately $150 value)</li><li>Two flex tickets ($180 value)</li><li>Producer/Sponsor credit for one show of your choice to be recognized each night of that show\'s performance and on marketing materials</li></ul></div></div></div>',10,0),
	(2,'Audition','',5,1,1,1,1,'<div class=\"audition-page bg-primary q-pa-md\"><div class=\"text-center\"><img src=\"https://mcusercontent.com/0022128dcf342c7a58eb81790/images/2352d04b-6fa5-4013-d2e6-8f31abdb4b1f.jpg\" style=\"max-width: 10rem;\"><h1 class=\"title text-white text-h5\">Audition for ACT’s “The Tale of the Allergist’s Wife” (An adult comedy) by Charles Busch and directed by Bob Fry</h1><h2 class=\"dates text-white text-h6 q-mt-md\">Auditions will be held Monday February 3 from 12-2pm. Performance dates are April 10, 11, 12 and 13. (Thurs-Sun)</h2><img src=\"https://mcusercontent.com/0022128dcf342c7a58eb81790/images/eb435b81-17e9-8142-f424-097d4192e9b6.jpg\" style=\"max-width: 25vw;\"></div><div class=\"body text-left q-ml-md\"><strong id=\"docs-internal-guid-9e4af0dd-7fff-22b7-ba56-afbd5b075d5e\">Open auditions: &nbsp;</strong>will be held on <u>Monday, February 3 from noon to 2PM</u>&nbsp;at the theater for the April show.&nbsp;&nbsp;No appointments are necessary. Just show up and read from the script.&nbsp;Contact Bob Fry&nbsp;<a href=\"mailto:otomandbob@gmail.com?subject=Audition%20Inquiry%3A%20The%20Allergists%20Wife&amp;body=Please%20include%20your%20name%2C%20email%20address%20and%20Whatsapp%20number%20with%20your%20inquiry%20below.%0A%0A\" target=\"_blank\" style=\"mso-line-height-rule: exactly;\n        -ms-text-size-adjust: 100%;\n        -webkit-text-size-adjust: 100%;\n        color: #007c89;\n        font-weight: normal;\n        text-decoration: underline;\">otomandbob@gmail.com</a> if this date and time are not convenient and/or you would like audition sides in advance.,<strong id=\"docs-internal-guid-9e4af0dd-7fff-22b7-ba56-afbd5b075d5e\">Rehearsals:</strong> will begin on February 17th.&nbsp;,<strong id=\"docs-internal-guid-9e4af0dd-7fff-22b7-ba56-afbd5b075d5e\">Performance dates: </strong>are April 10, 11, 12 and 13 at 3PM,<strong id=\"docs-internal-guid-da101483-7fff-12f5-35c8-e734a84c5eed\">Roles for 3 women and 2 men:&nbsp;&nbsp;</strong>As with all community theaters, your age is less important than acting ability and character interactions.&nbsp;The play is divided into two acts and seven scenes.The number of scenes each character appears in is shown in <strong>(</strong>parenthesis.<strong>)</strong><br><br><u><em>Marjorie Taub</em>:</u> An attractive, stylish woman, with a New York accent, in the throes of an epic depression tinged with raging frustration. (7)<br><br><u><em>Ira Taub</em>:</u> Marjorie’s husband, a retired doctor who runs a free clinic, described as good looking, highly energetic, and somewhat self absorbed. (6)<br><br><u><em>Frieda Tuchman</em>:</u> Marjorie’s mother, described as a small, shrunken woman who uses a walker (or cane), but is tough as cowhide. Has “digestive” problems that she likes to talk about. Tries to emphasize her Yiddish background. (6)<br><br><em><u>Lee Green:</u></em> An energetic, beautifully groomed woman, same age as Marjorie, has a very comfortable sense of her own sensuality. Knows everyone, been everywhere, and loves to talk about it. (5)<br><br><u><em>Mohammed:</em></u><em>&nbsp; </em>He is the Taub’s building doorman, described as boyish and very good looking. From the middle east, he must be able to speak with an accent but be understandable. Country/region of origin may be changed. (3)<br><br><strong>Questions</strong>:&nbsp; Bob Fry:&nbsp; <a href=\"mailto:otomandbob@gmail.com?subject=Audition%20Inquiry%3A%20The%20Allergists%20Wife&amp;body=Please%20include%20your%20name%2C%20email%20address%20and%20Whatsapp%20number%20with%20your%20inquiry%20below.%0A%0A\" target=\"_blank\" style=\"mso-line-height-rule: exactly;\n        -ms-text-size-adjust: 100%;\n        -webkit-text-size-adjust: 100%;\n        color: #007c89;\n        font-weight: normal;\n        text-decoration: underline;\">otomandbob@gmail.com</a></div></div><style>h1,\n  h2,\n  h3,\n  h4,\n  h5,\n  h6 {\n    display: block;\n    margin: 0;\n    padding: 0;\n  }\n\n\n  h1 {\n    color: #202020;\n    font-family: Helvetica;\n    font-size: 26px;\n    font-style: normal;\n    font-weight: bold;\n    line-height: 125%;\n    letter-spacing: normal;\n  }\n\n\n  h2 {\n    color: #202020;\n    font-family: Helvetica;\n    font-size: 22px;\n    font-style: normal;\n    font-weight: bold;\n    line-height: 125%;\n    letter-spacing: normal;\n  }\n\n\n  h3 {\n    color: #202020;\n    font-family: Helvetica;\n    font-size: 20px;\n    font-style: normal;\n    font-weight: bold;\n    line-height: 125%;\n    letter-spacing: normal;\n  }\n\n\n  h4 {\n    color: #202020;\n    font-family: Helvetica;\n    font-size: 18px;\n    font-style: normal;\n    font-weight: bold;\n    line-height: 125%;\n    letter-spacing: normal;\n  }</style>',10,0),
	(3,'News','https://www.facebook.com/cuencacommunitytheater',8,1,0,1,1,'',0,1),
	(4,'Home','/',0,1,0,0,0,'',0,0),
	(5,'Contact','',9,1,0,1,0,'',0,0),
	(6,'Gallery','',10,1,0,1,1,'',0,0),
	(7,'Volunteer','',6,1,1,1,1,'<div class=\"bg-primary q-pa-md\"><div>As a company of volunteers, we look for others who want to help bring live theater to Cuenca. We offer camaraderie, fun and an outlet for your creative and other talents. We have something for everybody to do and everybody is eligible to contribute.</div><div><br></div><div>Many ACT company members specialize in a particular aspect of theater and performing arts production. But many others wear lots of hats over the course of a season, taking a lead role in one show, directing the next, running sound or working with patrons in hospitality.</div><div><br></div><div>The options are many, and the opportunities whatever you want to make of them!</div><div><br></div><div><br></div><div class=\"q-mb-xl text-center\"><a href=\"https://yapatree.com/joining-act-theatre-curious-cuenca-podcast-ep-2/\" target=\"_blank\"><img class=\"podcast-image\" src=\"/images/joining-the-theater-podcast.jpeg\"></a><div class=\"text-subtitle1\"><span class=\"text-bold\">PODCAST</span> Click the pic for a recent Yapatree.com podcast<br>with some of our volunteers sharing about the fun of their experience.</div></div><div><ul><li><b>Acting and Directing</b></li><ul><li>Do you have experience performing or directing live theater? We\'d love to have you join the team! Want to learn to perform? Many of our best actors learned their craft at ACT, and we offer formal and \"on the job\" education. Interested in directing? Our experienced directors are always looking for students to shadow them as they assemble and produce a show.</li></ul><li><b>Backstage</b></li><ul><li>Backstage is an entire community, overseen by the stage manager who is the director\'s right hand during rehearsals, but is the absolute boss during performances. Meanwhile, the prop and costume folks find (or make) that hookah pipe or full-length fur coat that couldn\'t possibly exist in Cuenca, but must be in the show. Once the show begins, the stage hands must make the elegant Saturday night dinner party become the depressing Sunday morning remains of the debacle. In 30 seconds. In the dark.</li></ul><li><b>Leadership and Management</b></li><ul><li>There\'s the care and feeding of the physical plant. There\'s planning for the shows and analysis of what\'s required physically and financially to produce them and our other events. We need leaders to help recruit and manage groups of volunteers at our performances and in the community. Of course, we also need help running our finances and record keeping requires people with such experience. Ideally, each of these critical functions operate with a team rather than a single individual because we all want to go on vacation sometimes! If your strength is managing functions, people, or finance, we\'d love to explore where you might fit in this part of ACT.</li></ul><li><b>Tech</b></li><ul><li>Learn the latest in digital techniques for precisely controlling constellations of light fixtures to isolate sections of the stage or to subtly change the mood of a scene from benign to benighted simply painting with light.</li><li>Learn the ins and outs of theater sound, from the basics of recorded onstage doorbells and phone calls, to offstage sirens and traffic noise, to the elaborate suite needed to control and mix eight microphones and four channels of recorded sound to produce ACT\'s holiday productions of 1940s-style staged radio plays.</li></ul><li><b>Marketing</b></li><ul><li>Cuenca presents an interesting challenge for marketing an English-language theater.&nbsp; Ways to communicate with our target audience include both in-person and digital channels. So, in addition to folks skilled in photography, graphic arts, and effective copywriting, we are on the lookout for folks with digital skills. Are you a web design whiz? Do you rule in Social Media? Or are you the in-person maven, ready to host a desk at a feria or Newcomers\' Luncheon? Are you the person who can get our posters into every major restaurant in town?</li></ul><li><b>Set Design and Construction</b></li><ul><li>It\'s legos for giants! learn to get the most out of arranging pre-made modules, with just the right touch of paint and custom construction, to generate a starkly real urban bedroom for one show, but in the next production to simply suggest scenes that can shift from a village street to a forest path with the movement of a prop and a change of light.</li></ul></ul></div></div>',10,0),
	(8,'Season','',1,1,1,1,0,'',0,0),
	(9,'Tickets','',2,1,0,1,1,'',0,0),
	(10,'Get Involved','',3,1,1,1,1,'',0,0),
	(11,'About','',7,1,1,1,1,'<div class=\"about-us q-pa-md bg-primary\"><div class=\"q-mb-md text-bold  text-h4\">Who We Are</div><p>Azuay Community Theater (ACT) is an all-volunteer company dedicated to bringing quality, live performance, with an emphasis on theater to Cuenca, Ecuador.</p><p>Our mission is to serve our patrons, our players, and other volunteers.</p><p>For our patrons, we strive to provide top quality productions that would otherwise not be available to the Cuenca community, with an emphasis on English language theater.</p><p>For our performers and technicians, we make available an ever improving environment to learn and hone theatrical skills.</p><p>We give our volunteers an opportunity to participate in the broad range of activities required to maintain and promote an established, full production regional theater.</p><div class=\"q-mb-md text-bold text-h4\">Where to Find ACT</div><p>We are located in our own theater in El Centro, Cuenca on Antonio Vega Muoz 1446 between Coronel Tlbot and Estvez de Toral. <a class=\"text-italic\" href=\"https://maps.app.goo.gl/aWQdWrLWhpgE8Zkk6\" target=\"_blank\">Google Maps </a>. The theater is close to the Coronel Tlbot Tranva stop and buses serve our block on Antonio Vega Muoz continuously. We are a short walk from Parque Calderon (route shown on map below), and you can reach Parque San Sebastian with all its restaurants in just an 8 minute walk from the theater down Coronel Tlbot.</p><iframe allowfullscreen loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\" src=\"https://www.google.com/maps/embed?pb=!1m28!1m12!1m3!1d7969.454578744074!2d-79.01150295881433!3d-2.8947437883268887!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m13!3e0!4m5!1s0x91cd181170d314bd%3A0xecfe27b0240b68a7!2sCalderon%20Park%2C%204X3W%2B26J%2C%20Mariscal%20Sucre%2C%20Cuenca!3m2!1d-2.8974172!2d-79.0044893!4m5!1s0x91cd22b1edad03c5%3A0x24ed63d8e4d1ba1b!2sAzuay%20Community%20Theater%2C%20Vega%20Mu%C3%B1oz%2C%20y%2C%20Cuenca!3m2!1d-2.8914476!2d-79.0095515!5e0!3m2!1sen!2sec!4v1694472521817!5m2!1sen!2sec\" style=\"border:0; width:65vw; height: 45vw;\"></iframe><div class=\"text-caption\">Route from Parque Calderon to ACT</div><div class=\"direction-section\"><div><span class=\"text-bold\">Tranvia East bound </span>(e.g. coming from Gringolandia) exit at the Coronel Tlbot stop, walk less than a block west, make a right on Coronel Tlbot, walk three blocks north, turn right on Vega Muoz. We are about half way down the block on the North side of the street. The sign on the building says KO Boxing (but, don\'t worry you won\'t have to fight to get in). Someone will be waiting for you out front to direct you to our door.</div><div><span class=\"text-bold\">West bound tranvia </span>(e.g. coming from the airport) exit at the Mercado 3 de Noviembre exit (same exit as idiomART) walk less than a block west, make a right on Coronel Tlbot, walk two blocks north, turn right on Vega Muoz. We are about half way down the block on the North side of the street. The sign on the building says KO Boxing (but, don\'t worry you won\'t have to fight to get in). Our ACT Sandwich Board Sign and an actual person will be waiting for you out front to direct you to our door.</div><div><span class=\"text-bold\">Taxi </span>You can of course tell the taxi driver the exact address (Vega Muoz 14-34, entre Estvez de Toral y Coronel Tlbot) or simply exit at one of the intersections and walk half the block west from Estvez de Toral or East from Coronel Tlbot. We are about half way down the block on the North side of the street. The sign on the building says KO Boxing (but, don\'t worry you won\'t have to fight to get in). Someone will be waiting for you out front to direct you to our door.</div><div><span class=\"text-bold\">Bus </span>Many buses pass by or get very close to the theater. The Moovit app on your phone would be your best resource or give us the bus line you might use and we will do our best to help.</div><div><span class=\"text-bold\">Drive </span>Vega Muoz is a one way street running east to west, the closest intersection would be Estvez de Toral (one way running south to north). Make a left on Vega Muoz from Estvez de Toral ( best to get in the bus lane). We are about half way down the block on the North side of the street. The sign on the building says KO Boxing (but, don\'t worry you won\'t have to fight to get in). We have a parking lot right behind us with limited free parking. We will have the doors to the driveway open and waiting.</div></div><div class=\"spacer\"></div><div class=\"street-view\"><img src=\"/images/street-view.jpeg\"></div><div class=\"history\"><p>Founded in 2014, ACT\'s initial productions were rehearsed in members\' living rooms and presented in various restaurants and meeting facilities throughout the city. In April 2018, ACT obtained a permanent, beautiful home in an event auditorium originally constructed as part of a complex adjacent to the Hotel Oro Verde on Cuenca\'s Avenida Ordoez Lasso.</p><p>We expanded its stage and improved its sound and lights, and there we produced a wide range of plays and hosted other top class events like local music acts and magic shows. In December of 2022, we lost our lease on Ordoez Lasso and began a search for another permanent home during which we located a space on Juan Montalvo that served as a storage and rehearsal location for several months.</p><p>We are producing our exciting 2024-25 season of 6 more great shows starting with 4 performances on October 17 - 20 of Harvey by Mary Chase. This is our third full season of productions following the Global Pandemic. Check out our 2024-25 season rundown <a href=\"/2024-2025-season\">here</a> <a href=\"/gallery\">Gallery </a>page.</p></div>Interested in joining ACT? <a href=\"/volunteer\">Volunteer!</a></div>',0,0);

/*!40000 ALTER TABLE `navigation` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table performances
# ------------------------------------------------------------

DROP TABLE IF EXISTS `performances`;

CREATE TABLE `performances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `show_id` int(11) NOT NULL,
  `date` char(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci NOT NULL,
  `sold_out_target` int(11) DEFAULT 50,
  `fixr_link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `show_id` (`show_id`),
  CONSTRAINT `performances_ibfk_1` FOREIGN KEY (`show_id`) REFERENCES `shows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `performances` WRITE;
/*!40000 ALTER TABLE `performances` DISABLE KEYS */;

INSERT INTO `performances` (`id`, `show_id`, `date`, `sold_out_target`, `fixr_link`)
VALUES
	(1,1,'2025-06-12 14:00:00-05:00',50,NULL),
	(2,1,'2025-06-13 14:00:00-05:00',50,NULL),
	(3,1,'2025-06-14 14:00:00-05:00',50,NULL),
	(4,1,'2025-06-15 14:00:00-05:00',50,NULL),
	(9,3,'2023-12-01 14:00:00-05:00',50,NULL),
	(10,4,'2024-02-01 14:00:00-05:00',50,NULL),
	(11,5,'2023-09-01 14:00:00-05:00',50,NULL),
	(12,6,'2023-01-01 14:00:00-05:00',50,NULL),
	(13,7,'2024-06-01 14:00:00-05:00',50,NULL),
	(14,8,'2025-03-03 14:00:00-05:00',50,NULL),
	(15,8,'2025-03-04 14:00:00-05:00',50,NULL),
	(16,8,'2025-03-05 14:00:00-05:00',50,NULL),
	(17,8,'2025-03-06 14:00:00-05:00',50,NULL),
	(18,9,'2023-01-01 14:00:00-05:00',50,NULL),
	(19,10,'2022-12-01 14:00:00-05:00',50,NULL),
	(20,11,'2025-02-13 14:00:00-05:00',50,'https://fixr.co/en-US/event/mike-testing-event-tickets-966092386?'),
	(21,11,'2025-02-14 14:00:00-05:00',50,'https://fixr.co/en-US/event/mike-testing-event-tickets-966092386?'),
	(22,11,'2025-02-15 14:00:00-05:00',50,'https://fixr.co/en-US/event/mike-testing-event-tickets-966092386?'),
	(23,11,'2025-02-16 14:00:00-05:00',50,'https://fixr.co/en-US/event/mike-testing-event-tickets-966092386?'),
	(24,12,'2023-11-01 14:00:00-05:00',50,NULL),
	(25,13,'2022-09-01 14:00:00-05:00',50,NULL),
	(26,14,'2024-08-01 14:00:00-05:00',50,NULL),
	(27,15,'2024-05-01 14:00:00-05:00',50,NULL),
	(28,16,'2024-10-17 14:00:00-05:00',50,NULL),
	(29,16,'2024-10-18 14:00:00-05:00',50,NULL),
	(30,16,'2024-10-19 14:00:00-05:00',50,NULL),
	(31,16,'2024-10-20 14:00:00-05:00',50,NULL),
	(32,17,'2023-02-01 14:00:00-05:00',50,NULL),
	(33,18,'2024-12-12 14:00:00-05:00',50,NULL),
	(34,18,'2024-12-13 14:00:00-05:00',50,NULL),
	(35,18,'2024-12-14 14:00:00-05:00',50,NULL),
	(36,18,'2024-12-15 14:00:00-05:00',50,NULL),
	(37,19,'2023-07-01 14:00:00-05:00',50,NULL);

/*!40000 ALTER TABLE `performances` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table sendgrid_responses
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sendgrid_responses`;

CREATE TABLE `sendgrid_responses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) DEFAULT NULL,
  `status_code` int(11) DEFAULT NULL,
  `header` longtext DEFAULT NULL,
  `body` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



# Dump of table shows
# ------------------------------------------------------------

DROP TABLE IF EXISTS `shows`;

CREATE TABLE `shows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `writer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `tagline` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `director` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `poster` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `sold_out_target` int(11) DEFAULT 50,
  `_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `shows` WRITE;
/*!40000 ALTER TABLE `shows` DISABLE KEYS */;

INSERT INTO `shows` (`id`, `name`, `writer`, `tagline`, `description`, `director`, `poster`, `sold_out_target`, `_id`)
VALUES
	(1,'Dial \'M\' For Murder','Frederick Knott','A suspenseful dance of deception and danger.','A husband\'s plan to murder his wife for her money unravels in this gripping thriller of twists and turns.','Gerald Cole','673f70e6f3b47.jpeg',50,'dial-\'m\'-for-murder-2024'),
	(3,'Miracle on 34th Street','Valentine Davies','Rediscover the magic of believing.','<p>When a man claiming to be the real Kris Kringle goes on trial, one little girl and her mother learn the true meaning of faith and the holiday spirit.</p>','Kit Thornton','673f70e6f2857.png',50,'miracle-on-34th-street-2023'),
	(4,'The Prisoner of 2nd Avenue','Neil Simon','Life in New York City has never been funnier—or more stressful!','<p>This dark comedy follows a Manhattan couple grappling with unemployment, burglary, and urban chaos in Neil Simon\'s inimitable style.</p>','Rick Snyder','673f70e6f2b46.png',50,'the-prisoner-of-2nd-avenue-2024'),
	(5,'Trumbo: Red, White, & Blacklisted','Christopher Trumbo','The personal and political collide.','<p>The story of Dalton Trumbo, one of the Hollywood Ten, and his fight against the blacklist during the McCarthy era—told with wit and passion.</p>','Rick Snyder','673f70e6f2257.jpg',50,'trumbo-red-white-blacklisted-2023'),
	(6,'Fundamentals of Stagecraft Workshop','Paula Bailey','Learn the essentials of stage production.','<p>Join <strong>Paula Bailey</strong> for a hands-on workshop covering the basics of stagecraft, including lighting, sound, and set design. Perfect for beginners and aspiring tech crew members!</p>','Paula Bailey','673f70e6f1b7f.jpg',50,'fundamentals-of-stagecraft-workshop-2023'),
	(7,'Murder at the Howard Johnson\'s','Ron Clark and Sam Bobrick','A hilarious mix of love, betrayal, and murder!','<p>A love triangle at a motel turns into a riotous comedy of errors as a wife, her lover, and her husband all plot against each other—with surprising results.</p>','Rick Snyder','673f70e6f302b.jpeg',50,'murder-at-the-howard-johnsons-2024'),
	(8,'The Tale of the Allergist\'s Wife','Charles Busch','A midlife crisis with a hilariously eccentric twist.','An Upper West Side woman’s existential crisis is upended when a flamboyant childhood friend re-enters her life, sparking chaos and self-discovery.','Bob Fry','673f70e6f3a42.jpeg',50,'the-tale-of-the-allergists-wife-2025'),
	(9,'Funraiser 2023: Musical Review','Deb Davis, Cindy Benson, Glenn Gano, Mark Deckard','An unforgettable evening of song and celebration!','<p>Featuring performances by <strong>Deb Davis</strong>, <strong>Cindy Benson</strong>, <strong>Glenn Gano</strong>, and <strong>Mark Deckard</strong>, this musical review promises to delight audiences while supporting the arts.</p>','','673f70e6f1a32.jpg',50,'funraiser-2023-musical-review-2023'),
	(10,'It\'s A Wonderful Life','Adapted by Joe Landry','Rediscover the timeless story of hope and community.','<p>Follow George Bailey\'s journey as he discovers the profound impact his life has had on his family and town. A heartwarming classic reimagined for the stage.</p>','Paula Bailey','673f70e6f17c8.jpg',50,'its-a-wonderful-life-2022'),
	(11,'Same Time, Next Year','Bernard Slade','A love story that spans decades—one weekend at a time.','A man and woman, each married to someone else, meet for an annual romantic rendezvous over 25 years, exploring love, change, and connection.','Jeanne McCafferty','67449947a2387.jpeg',50,'same-time-next-year-2025'),
	(12,'Cemetery Club','Ivan Menchell','Friendship, love, and laughter beyond loss.','<p>Three widowed friends meet regularly at their husbands\' graves—until a charming new suitor changes everything in this touching and funny story.</p>','Gerald Cole','673f70e6f24fb.png',50,'cemetery-club-2023'),
	(13,'It Had To Be You','Renee Taylor and Joseph Bologna','A screwball comedy with a romantic twist.','<p>An eccentric actress and an uptight producer lock horns—and hearts—in this hilarious holiday-themed romantic comedy.</p>','Paula Bailey','673f70e6f110e.jpg',50,'it-had-to-be-you-2022'),
	(14,'The Waverly Gallery','Kenneth Lonergan','A heartfelt look at family, memory, and loss.','<p>An elderly woman\'s struggle with dementia and its impact on her family is told with warmth, humor, and heartbreak in this Pulitzer Prize-nominated play.</p>','Bob Fry','673f70e6f332e.jpeg',50,'the-waverly-gallery-2024'),
	(15,'Blithe Spirit','Noël Coward','A supernatural comedy classic.','<p>A séance gone wrong brings back the ghost of a writer\'s late wife, wreaking havoc on his current marriage in this witty, otherworldly farce.</p>','Gerald Cole','673f70e6f2dc1.png',50,'blithe-spirit-2024'),
	(16,'Harvey','Mary Chase','A six-foot-tall invisible rabbit—and a lesson in kindness.','Elwood P. Dowd befriends an invisible rabbit named Harvey, bringing charm, chaos, and joy to everyone around him.','Gerald Cole','673f70e6f3590.jpeg',50,'harvey-2024'),
	(17,'An Evening of Short Plays','Tennessee Williams, Robert Anderson, Gert Hofmann, Robert Scott','A showcase of four unique stories by celebrated writers.','<ol><li><strong>A Perfect Analysis Given by a Parrot</strong> by Tennessee Williams</li><li><strong>I\'m Herbert</strong> by Robert Anderson</li><li><strong>Our Man in Madras</strong> by Gert Hofmann</li><li><strong>Plan B</strong> by Robert Scott</li></ol><p>Enjoy an eclectic evening of wit, drama, and humor with these short but memorable plays.</p>','Jeanne McCafferty, Marku Sario, Bob Fry, Rick Snyder','673f70e6f1ce5.png',50,'an-evening-of-short-plays-2023'),
	(18,'A Christmas Carol','Charles Dickens','Adapted for radio by Anthony Palermo','<p>Adapted as a live radio production by Anthony E. Palermo, ACT cast members bring to live the beloved Charles Dickens story of Ebenezer Scrooge, a selfish, greedy businessman who is transformed after being visited by the ghost of his former business partner and the spirits of Christmas Past, Present, and Yet to Come. Live and recorded sound effects lend authenticity to the radio production form. Our generator guarantees all performances. 2pm curtain time means you get home in daylight.</p>','Cody Hamilton and Saralee Squires','673f70e6f372a.jpeg',2,'a-christmas-carol-2024'),
	(19,'The Last Romance','Joe DiPietro','A second chance at love—filled with surprises.','<p>An unexpected meeting in a dog park brings two seniors together in this bittersweet comedy about love, loss, and pursuing happiness at any age.</p>','Rick Snyder','673f70e6f1fa7.jpg',50,'the-last-romance-2023');

/*!40000 ALTER TABLE `shows` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table tickets
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tickets`;

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `performance_id` int(11) NOT NULL,
  `purchaser_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci NOT NULL,
  `purchaser_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci NOT NULL,
  `purchaser_phone` varchar(255) DEFAULT NULL,
  `assigned_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `num_tickets` int(11) NOT NULL,
  `payment_method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `order_date` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci NOT NULL,
  `payment_date` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  `purchased_qty` int(11) DEFAULT NULL,
  `fixr_ticket_id` int(11) DEFAULT NULL,
  `fixr_response` longtext DEFAULT NULL,
  `fixr_user_uuid` char(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_id` (`performance_id`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`performance_id`) REFERENCES `performances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;

INSERT INTO `tickets` (`id`, `performance_id`, `purchaser_name`, `purchaser_email`, `purchaser_phone`, `assigned_name`, `num_tickets`, `payment_method`, `order_date`, `payment_date`, `purchased_qty`, `fixr_ticket_id`, `fixr_response`, `fixr_user_uuid`)
VALUES
	(1,14,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',3,'paypal','2025-01-15','2025-02-20',NULL,NULL,NULL,NULL),
	(2,14,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',2,'paypal','2025-01-15','2025-02-21',NULL,NULL,NULL,NULL),
	(3,21,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',1,'paypal','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(4,22,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',1,'paypal','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(5,22,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',3,'paypal','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(6,22,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',2,'paypal','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(7,23,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',2,'paypal','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(8,23,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',3,'paypal','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(9,23,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',2,'paypal','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(10,23,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',3,'paypal','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(11,22,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',2,'transfer','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(12,21,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',2,'flex','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(13,23,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',1,'fixr','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(14,20,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',2,'fixr','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(15,20,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',2,'fixr','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(16,20,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',1,'fixr','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(17,20,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',1,'fixr','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(18,23,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',1,'fixr','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(19,23,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',1,'fixr','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(20,23,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',2,'fixr','2025-01-15',NULL,NULL,NULL,NULL,NULL),
	(21,23,'Mike Casto','agps.guru@gmail.com','0984522104','Mike Casto',1,'fixr','2025-01-15',NULL,NULL,NULL,NULL,NULL);

/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci NOT NULL,
  `pass_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;

INSERT INTO `users` (`id`, `name`, `email`, `pass_hash`, `token`)
VALUES
	(1,'Rick Snyder','rick.snyder57@gmail.com','$2y$10$sQWW0EITfLZX3Flfx7L1ve6bdZ63I7.gwPy2/8mp0quvjDQHu1OL.',NULL),
	(2,'Mike Casto','castoware@gmail.com','$2y$10$/CQoncyb3c9wchtLVPLFQeX2DbSUJCoO4iuImLo.QBU32G9LhXxki','act-eofa-hy5q0977u6');

/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
