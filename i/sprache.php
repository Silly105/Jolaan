<?php

if(session_status() == PHP_SESSION_NONE){session_start();}

global $s_lang;
$sprache = $_REQUEST['s'];

if($sprache == "")
{
  $sprache = $_SESSION['s'];
}

if(($sprache == "en") || ($ressource == "en"))
{
//**********************************************************************************************
//**********************************************************************************************
/***********************************************************************************************
 _______  _        _______  _       _________ _______
(  ____ \( (    /|(  ____ \( \      \__   __/(  ____ \|\     /|
| (    \/|  \  ( || (    \/| (         ) (   | (    \/| )   ( |
| (__    |   \ | || |      | |         | |   | (_____ | (___) |
|  __)   | (\ \) || | ____ | |         | |   (_____  )|  ___  |
| (      | | \   || | \_  )| |         | |         ) || (   ) |
| (____/\| )  \  || (___) || (____/\___) (___/\____) || )   ( |
(_______/|/    )_)(_______)(_______/\_______/\_______)|/     \|

***********************************************************************************************/
//**********************************************************************************************
//**********************************************************************************************

  $_SESSION['s'] = "en";
  $s_lang = "en";

//##################################################################################################
//###########   conanmeldung.php   #################################################################
//##################################################################################################
  
  $s_darstellung1 = "butterfly";
  $s_darstellung2 = "ape";
  $s_darstellung3 = "dragon";
  $s_darstellung4 = "dog";
  $s_darstellung5 = "hedgehog";
  $s_darstellung6 = "cat";
  $s_darstellung7 = "fox";
  $s_darstellung8 = "mouse";
  $s_darstellung9 = "horse";
  $s_darstellung10 = "bird";

  $s_anmeldebutton = "Register";

  $s_email_bereits_reg_title = "Email address already in use";
  $s_email_bereits_reg_text = "The email address with which you'd like to register is already in use for this event. Please register using your own email address. Press back in your browser to alter your information.";

  $s_mailbest_mailsubject = " - Please complete your registration";
  $s_mailbest_mailtext1 = "Registration</title></HEAD><BODY TEXT='#000000'>Hello"; //HTML-Code nicht anpassen!
  $s_mailbest_mailtext2 = "To complete your registration, please follow this link:";
  $s_mailbest_mailtext3 = "(should clicking the link not work, please copy paste the link to your webbrowser.)<br><br>See you soon!<br>Your Westorga"; //HTML-Code nicht anpassen!
  $s_mailbest_pagetitle = "Registration allmost done.";
  $s_mailbest_pagecontent = "Your registration has been sent. <b>You will receive an email with a confirmation link in a few minutes.</b> Your registration will be complete after you have clicked this confirmation link!"; //HTML-Code nicht anpassen!

  $s_anmelderror_title = "Registration FAILED!";
  $s_anmelderror_content = "Your information could not be registered. Should this problem persist, please contact the <a href='$serveradresse/kontakt.php' target='_new'>Orga</a>.";

  $s_datenunvoll_title = "Registration <font color='#FF0000'><u>failed</u></font>";
  $s_datenunvoll_content = "There was an error in the provided information. You need to fill out all fields marked by an asteriks, agree with the our terms and conditions, and correctly name the animal shown in the picture to register.<br>Please click back in your webbrowser and correct your information.";

  $s_anmeldsperre_title = "Online-registration closed";
  $s_anmeldsperre_content = "The Online Registration has been closed by the administrator for maintanance, or because the maximum number of event participants has been reached.";

  $s_kurzfragebogenzumchar_ueberschrift = "Player Characters";
  $s_kurzfragebogenzumchar_einleitung = "Non player characters may leave these fields empty and continue below...";
  $s_kurzfragebogenzumchar_cname = "Charakter name";
  $s_kurzfragebogenzumchar_klasse = "Class";
  $s_kurzfragebogenzumchar_contage = "Number of days played";
  $s_kurzfragebogenzumchar_spielergruppe = "Player group";
  $s_kurzfragebogenzumchar_siegel = "Seal affiliation";
  $s_kurzfragebogenzumchar_gundl = "Stories and Legends";
  $s_kurzfragebogenzumchar_dieben = "Thieves / theft of goods ";
  $s_kurzfragebogenzumchar_magiekundig = "Magic knowledge";
  $s_kurzfragebogenzumchar_alchemiekundig = "Alchemy knowledge";
  $s_kurzfragebogenzumchar_fde = "Friendship of the elements";
  $s_kurzfragebogenzumchar_spaehrenk = "Sphere traveler";
  $s_kurzfragebogenzumchar_rituale = "Rituals planned?";
  $s_kurzfragebogenzumchar_rituale_text = "(Inform the Orga at least 8 weeks prior to the event!!)";
  $s_kurzfragebogenzumchar_artefakte = "Special features, artifacts, tribes ?";

  $s_nsctext_ueberschrift = "NPC's";
  $s_nsctext_einleitung = "Player Characters may leave these fields empty and continue below...";
  $s_nsctext_ausruestung = "Equipment";
  $s_nsctext_ausruestung_text = "Do you have gear that you could bring to the event?";
  $s_nsctext_wunschrolle = "Desired role";
  $s_nsctext_wunschrolle_text = "Is there anything you really wanted to play for as long as you can remember?";

  $s_teilsperrung_keinewahl = "This choice is as you see no more. ";
  $s_teilsperrung_scvoll = "Unfortunately, there is no more room for Player Characters. Please join as NPC! :)";
  $s_teilsperrung_scvoll2 = "Unfortunately, there is no more room for Player Characters.<br>Please join as NPC! :)";
  $s_teilsperrung_nscvoll = "Unfortunately, there is no more room for NPC's. Please join as Player Character! :)";
  $s_teilsperrung_nscvoll2 = "Unfortunately, there is no more room for NPC's.<br>Please join as Player Character!! :)";

  $s_menschentest_ueberschrift = "Humanity test";
  $s_menschentest_1 = "Unfortunately, we are forced to implement this humanity test to prevent automated programs from registering for our event. Please write down in the field below, what animal you see in the picture. If you are not certain what animal is shown, take a look at";
  $s_menschentest_2 = "this list</a> Here you will find the names of all animals used in the test.";
  $s_menschentest_3 = "This is the animal I can see:<br>(without punctuation, for example &quot;Fish&quot;)";

  $s_sonderfelder_ueberschrift = "Special questions to this event";

  $s_teilnehmerdaten_ueberschrift = "Participant data";
  $s_teilnehmerdaten_vorname = "First name";
  $s_teilnehmerdaten_nachname = "Surname";
  $s_teilnehmerdaten_anschrift = "Street and house number";
  $s_teilnehmerdaten_plz = "Zip code";
  $s_teilnehmerdaten_ort = "City";
  $s_teilnehmerdaten_geburt = "Date of birth";
  $s_teilnehmerdaten_geschlecht = "Gender";
  $s_teilnehmerdaten_geschlecht_k = "Not specified";
  $s_teilnehmerdaten_geschlecht_m = "Male";
  $s_teilnehmerdaten_geschlecht_w = "Female";
  $s_teilnehmerdaten_geschlecht_d = "Other";
  $s_teilnehmerdaten_schwanger = "Pregnant";
  $s_teilnehmerdaten_essgewohnheiten = "Special eating habits";
  $s_teilnehmerdaten_essgewohnheiten2 = "(Please let us know what foods you do not eat.)";
  $s_teilnehmerdaten_medausbildung = "Medical training";
  $s_teilnehmerdaten_medausbildung_arzt = "Doctor";
  $s_teilnehmerdaten_medausbildung_rettungsassi = "Paramedic";
  $s_teilnehmerdaten_medausbildung_mediziner = "Practical medic";
  $s_teilnehmerdaten_medausbildung_sani = "Medic";
  $s_teilnehmerdaten_medausbildung_ersthelfer = "First Aid";
  $s_teilnehmerdaten_medausbildung_keine = "None";
  $s_teilnehmerdaten_medausbildung_wirmeldenuns = "(* we'll get back to you)";
  $s_teilnehmerdaten_telefon = "Telephone number";
  $s_teilnehmerdaten_handy = "Cell-phone number";
  $s_teilnehmerdaten_email = "Email address";

  $s_teilnehmerdaten_email2 = "(You will receive a comfirmation mail at this address, please make sure it is correct. Any address may only be used once to register, so please make sure you are using your own email address.)";
  $s_teilnehmerdaten_email3 = "(Any address may only be used once to register.)";
  $s_teilnehmerdaten_email_nochmal = "Email address (again, please)";

  $s_teilnehmerdaten_sc = "Player Character";
  $s_teilnehmerdaten_nsc = "Non Player Character";
  $s_teilnehmerdaten_spieler = "Player";
  $s_teilnehmerdaten_waehlen = "Please select";
  $s_teilnehmerdaten_allergien = "Allergies";
  $s_teilnehmerdaten_allergien2 = "(Even when this seems a bit double, please fill this out so we may find it faster in case of an emergency.)";
  $s_teilnehmerdaten_krankheiten = "illnesses";
  $s_teilnehmerdaten_phobien = "phobias";
  $s_teilnehmerdaten_pflichtangaben = "Fields marked with * will need to be filled out to enable you to register.";

  $s_diverses_ueberschrift = "Miscellaneous";
  $s_diverses_bemerkungen = "Comments, additions, Requests for accommodation or anything you want to let us know";
  $s_diverses_wiedraufgekommen = "Just very briefly: How did you hear about our event?";
  $s_diverses_kennzeichen = "When you will arrive by car: Licence plate number (<i>only</i> the driver please!)";
  $s_diverses_stimmungsbild = "Concept question: What do you value most?";
  $s_diverses_stimmung_k = "Fighting";
  $s_diverses_stimmung_p = "Plotting";
  $s_diverses_stimmung_z = "Performing Magic";

  $s_anmeldebogen_ueberschrift = "Online-Registration";
  $s_anmeldebogen_flyersehen = "View flyer";

  $s_abschluss_ueberschrift = "Legal and conclusive things";
  $s_abschluss_agb1 = "I have read the";
  $s_abschluss_agb2 = "terms and conditions</a> and accept these.";
  $s_abschluss_agb_j = "Yes";
  $s_abschluss_agb_n = "No";
  $s_abschluss_agb3 = "Acceptance of the terms and conditions is required for participation in this event.";
  $s_abschluss_mailspeichern1 = "All information entered here will be used to organise the larp event, and will be deleted after the event. Please read our <a href='http://westliches-siegel.de/articles.php?article_id=74' target='_new'>privacy policy</a>.";
  $s_abschluss_mailspeichern2 = "I agree that my data is saved and processed for this purpose and that I will recieve emails regarding my event registration.";
  $s_abschluss_mailspeichern_j = "Yes, please save and send mail";
  $s_abschluss_mailspeichern_n = "No, I decided not to register for this event.";
  $s_abschluss_flyerverweis = "You can find prices, bank details, contact details and more information in the current";
  $s_abschluss_flyer = "flyer";
  $s_abschluss_beifragen = "If you have any questions regarding your registration, please contact:";
  $s_abschluss_anmeldverbind = "Registration is binding after you have clicked on the confirmation link in your email and transferred the event fee!";
  $s_abschluss_kurzdruck = "(Please press only once and wait for confirmation)";

  $s_freischaltung_idfehler_title = "ID not found ";
  $s_freischaltung_idfehler_content = "No registration with this ID was found in the database. If the error occurs again, please inform the administrator.";
  $s_freischaltung_dbfehler_title = "Error";
  $s_freischaltung_dbfehler_content = "Your activation could not be entered in the database. If the error occurs again, please inform the administrator.";
  $s_freischaltung_ok_title = "Registration complete";
  $s_freischaltung_ok_content1 = "Thank you,";
  $s_freischaltung_ok_content2 = ".<br><br>Your email address is confirmed and you are now registered for the event:";
  $s_freischaltung_ok_content3 = "<br><b>Your registration will only be binding after you transferred the event fee.</b><br>You will receive a validation within a few days.<br><br>Prices, bank details, contact details and more information are found in the latest ";
  $s_freischaltung_ok_content4 = "Thank you and see you at the event.<br><br>Your<br>Western Orga";
  $s_freischaltung_bereitsfrei_title = "Already activated";
  $s_freischaltung_bereitsfrei_content = "Your registration has already been activated. If you have any questions, please write us an email to:";
  $s_freischaltung_codefalsch_title = "Incorrect code";
  $s_freischaltung_codefalsch_content = "The email-confirmation code provided by you was incorrect. Maybe you have forgotten a few characters? Should the problem persist, please contact the Orga:";
  $s_freischaltung_idnichtgefunden_title = "ID not found";
  $s_freischaltung_idnichtgefunden_content = "No event with that ID was found in the database. Should this problem persist, please inform the administrator.";

  $s_anmeldefehler_menschentest = "The humanity test (recognise animals) has not been passed. Please try again with a new animal.";
  $s_anmeldefehler_vorname = "The first name field was empty.";
  $s_anmeldefehler_nachname = "The surname field was empty.";
  $s_anmeldefehler_strasse = "The street field was empty.";
  $s_anmeldefehler_plz = "The zip code field was empty.";
  $s_anmeldefehler_wohnort = "The city field was empty.";
  $s_anmeldefehler_geburtsjahr = "The year of birth field was empty.";
  $s_anmeldefehler_email_bereitsreg = "This email adress has already been registered.";
  $s_anmeldefehler_email_leer = "The email field was empty.";
  $s_anmeldefehler_email_nochmal_leer = "The email check field was empty.";
  $s_anmeldefehler_email_nochmal_falsch = "The two entered email addresses were not eqal.";
  $s_anmeldefehler_agb = "The terms and conditions have not been accepted.";
  $s_anmeldefehler_mailspeichern = "You did not gave permission to save your data.";
  $s_anmeldefehler_scnsc = "It was not specified if this is a player or NPC-registration.";
  $s_anmeldefehler_hinweistext = "There has been an error with your registration";
  $s_anmeldefehler_ende = "Please correct your data for a succesful registration.";

//##################################################################################################
//###########   index.php   ########################################################################
//##################################################################################################

  $s_index_title = "Welcome to the event system of the Western Seal";
  $s_index_introtext = "This page is part of the event management system of the Western Seal. Here you will find all information to the events and you will have the possibillity to register online.";
  $s_index_veranst_laeuft = "Ongoing event";
  $s_index_veranst_aktuell = "Current event";
  $s_index_veranst_keine = "No current event";
  $s_index_datestring = "j F Y";
  $s_index_mysqldatestring = "%e %M %Y";

  $s_index_scplaetze = "PC places available";
  $s_index_nscplaetze = "NPC places available";
  $s_index_stand = "Last update"; //meaning "registration update 12.12.2005 "
  $s_index_zumflyer = "To the flyer";
  $s_index_zuranmeldung = "To online-registration";
  $s_index_zumforum = "To the forums";

  $s_countdown_noch = "T minus"; //5 days, 20 hours remaining ...
  $s_countdown_dps = "s";
  $s_countdown_hps = "s";
  $s_countdown_mps = "s";
  $s_countdown_sps = "s";
  $s_countdown_d = "Day";
  $s_countdown_h = "Hour";
  $s_countdown_m = "Minute";
  $s_countdown_s = "Second";

//##################################################################################################
//###########   hpanmeldung.php   ##################################################################
//##################################################################################################

  $s_hpanmeldung_title = "Registration";
  $s_hpanmeldung_orgalogin = "Orga Login";
  $s_hpanmeldung_explain = "Login for members of the Orga and SL/game masters for management purposes";
  $s_hpanmeldung_lostpw = "I have forgotten my password";
  $s_hpanmeldung_mail = "Email";
  $s_hpanmeldung_pw = "Password";

//##################################################################################################
//###########   western_design.php   ###############################################################
//##################################################################################################

  $s_design_menu_title = "Menu;";
  $s_design_menu_nav = "Navigation";
  $s_design_menu_hp = "Consystem Homepage";
  $s_design_menu_back = "Back to Website";
  $s_design_menu_forum = "Forum";
  $s_design_menu_impressum = "Impressum";
  $s_design_menu_datenschutzerklaerung = "Privacy policy";
  $s_design_menu_orga = "Orga pages";
  $s_design_menu_login = "Login";
  $s_design_easteregg = "Welcome to the hall of the Nyame and Archon in Grian Quihenya";

//##################################################################################################
//###########   tierliste.php   ####################################################################
//##################################################################################################

  $s_tierliste_title = "Animal list for humanity test";
  $s_tierliste_explainer = "Here's a list of animals for the. &quot;humanity test&quot;. To not make it too easy for non humans to register, some animals listed here will never show on the pictures.";

  //WICHTIG: Diese ersten 10 >exakt< so wie oben uebersetzen, nur mit grossem Anfangsbuchstaben
  $s_tierliste_schmetterling = "Butterfly";
  $s_tierliste_affe = "Ape";
  $s_tierliste_drache = "Dragon";
  $s_tierliste_hund = "Dog";
  $s_tierliste_igel = "Hedgehog";
  $s_tierliste_katze = "Cat";
  $s_tierliste_fuchs = "Fox";
  $s_tierliste_maus = "Mouse";
  $s_tierliste_pferd = "Horse";
  $s_tierliste_vogel = "Bird";
  //------
  $s_tierliste_einhorn = "Unicorn";
  $s_tierliste_dachs = "Badger";
  $s_tierliste_frosch = "Frog";
  $s_tierliste_tiger = "Tiger";
  $s_tierliste_biber = "Beaver";
  $s_tierliste_schlange = "Snake";
  $s_tierliste_zergling = "Zergling";
  $s_tierliste_muecke = "Mosquito";
  $s_tierliste_regenwurm = "Earthworm";
  $s_tierliste_hoellenhund = "Hellhound";
  $s_tierliste_nilpferd = "Hippo";
  $s_tierliste_baer = "Bear";
  $s_tierliste_faultier = "Sloth";
  $s_tierliste_nashorn = "Rhino";
  $s_tierliste_hamster = "Hamster";
  $s_tierliste_kaefer = "Bug";
  $s_tierliste_fisch = "Fish";
}
else //($sprache == "de")
{
//**********************************************************************************************
//**********************************************************************************************
/***********************************************************************************************
 ______   _______          _________ _______  _______
(  __  \ (  ____ \|\     /|\__   __/(  ____ \(  ____ \|\     /|
| (  \  )| (    \/| )   ( |   ) (   | (    \/| (    \/| )   ( |
| |   ) || (__    | |   | |   | |   | (_____ | |      | (___) |
| |   | ||  __)   | |   | |   | |   (_____  )| |      |  ___  |
| |   ) || (      | |   | |   | |         ) || |      | (   ) |
| (__/  )| (____/\| (___) |   | |   /\____) || (____/\| )   ( |
(______/ (_______/(_______)   )_(   \_______)(_______/|/     \|

***********************************************************************************************/
//**********************************************************************************************
//**********************************************************************************************

  $_SESSION['s'] = "de";  
  $s_lang = "de";

//##################################################################################################
//###########   conanmeldung.php   #################################################################
//##################################################################################################

  $s_darstellung1 = "schmetterling";
  $s_darstellung2 = "affe";
  $s_darstellung3 = "drache";
  $s_darstellung4 = "hund";
  $s_darstellung5 = "igel";
  $s_darstellung6 = "katze";
  $s_darstellung7 = "fuchs";
  $s_darstellung8 = "maus";
  $s_darstellung9 = "pferd";
  $s_darstellung10 = "vogel";

  $s_anmeldebutton = "Anmelden";

  $s_email_bereits_reg_title = "Email Adresse bereits registriert";
  $s_email_bereits_reg_text = "Die Email-Adresse, mit der Du Dich anmelden m&ouml;chtest, ist bereits f&uuml;r diesen Con angemeldet. Bitte immer nur mit der eigenen Email-Adresse anmelden. Dr&uuml;cke auf &quot;Zur&uuml;ck&quot; in Deinem Webbrowser, um Deine Angaben zu &uuml;berarbeiten.";

  $s_mailbest_mailsubject = " - Vollende Deine Anmeldung";
  $s_mailbest_mailtext1 = "Anmeldung</title></HEAD><BODY TEXT='#000000'>Hallo"; //HTML-Code nicht anpassen!
  $s_mailbest_mailtext2 = "Um Deine Anmeldung zu vollenden, folge einfach diesem Link:";
  $s_mailbest_mailtext3 = "(sollte das anklicken nicht funktionieren, kannst Du den Link auch einfach kopieren und in der Adresszeile Deines Webbrowsers wieder einf&uuml;gen.)<br><br>Bis dann!<br>Deine Westorga"; //HTML-Code nicht anpassen!
  $s_mailbest_pagetitle = "Anmeldung fast fertig";
  $s_mailbest_pagecontent = "Deine Anmeldung ist gesendet worden. <b>Du erh&auml;ltst in den n&auml;chsten Minuten eine Email mit einem Best&auml;tigungslink</b>. Erst durch das Klicken von diesem Link ist Deine Anmeldung komplett!"; //HTML-Code nicht anpassen!

  $s_anmelderror_title = "Anmeldung FEHLGESCHLAGEN!";
  $s_anmelderror_content = "Die Daten konnten NICHT eingetragen werden. Wenn das Problem l&auml;nger besteht, wende Dich bitte an die <a href='$serveradresse/kontakt.php' target='_new'>Orga</a>.";

  $s_datenunvoll_title = "Anmeldung <font color='#FF0000'><u>fehlgeschlagen</u></font>";
  $s_datenunvoll_content = "Es gab einen Fehler bei Deinen Daten. Du musst die mit Sternchen markierten Felder ausf&uuml;llen, die AGB's best&auml;tigt und das Tier richtig benannt haben, um Dich anmelden zu k&ouml;nnen<br>Bitte dr&uuml;cke auf &quot;Zur&uuml;ck&quot; in Deinem Webbrowser und korrigiere die Angaben.";

  $s_anmeldsperre_title = "Online-Anmeldung gesperrt";
  $s_anmeldsperre_content = "Im Moment ist keine Online-Anmeldung m&ouml;glich, da diese vom Administrator gesperrt wurde. Entweder wir warten gerade die Anmeldescripte, oder alle Pl&auml;tze sind belegt.";

  $s_kurzfragebogenzumchar_ueberschrift = "SC's";
  $s_kurzfragebogenzumchar_einleitung = "NSC's lassen hier bitte alles frei, und machen unten weiter...";
  $s_kurzfragebogenzumchar_cname = "Charaktername";
  $s_kurzfragebogenzumchar_klasse = "Klasse";
  $s_kurzfragebogenzumchar_contage = "Contage";
  $s_kurzfragebogenzumchar_spielergruppe = "Spielergruppe";
  $s_kurzfragebogenzumchar_siegel = "Siegelzugeh&ouml;rigkeit";
  $s_kurzfragebogenzumchar_gundl = "Geschichten &amp; Legenden";
  $s_kurzfragebogenzumchar_dieben = "Dieben / Warendiebstahl";
  $s_kurzfragebogenzumchar_magiekundig = "Magiekundig";
  $s_kurzfragebogenzumchar_alchemiekundig = "Alchemiekundig";
  $s_kurzfragebogenzumchar_fde = "Freundschaft der Elemente";
  $s_kurzfragebogenzumchar_spaehrenk = "Sph&auml;renkundig";
  $s_kurzfragebogenzumchar_rituale = "Rituale geplant?";
  $s_kurzfragebogenzumchar_rituale_text = "(Orga sp&auml;testens 8 Wochen vor Con informieren!)";
  $s_kurzfragebogenzumchar_artefakte = "Besonderheiten, Artefakte, Tribes?";

  $s_nsctext_ueberschrift = "NSC's";
  $s_nsctext_einleitung = "SC's lassen dies hier bitte frei, und machen unten weiter...";
  $s_nsctext_ausruestung = "Ausr&uuml;stung";
  $s_nsctext_ausruestung_text = "Hast Du Ausr&uuml;stung, die Du zum Con mitbringen kannst?";
  $s_nsctext_wunschrolle = "Wunschrolle";
  $s_nsctext_wunschrolle_text = "Was wolltest Du schon immer mal spielen?";

  $s_teilsperrung_keinewahl = "Diese Wahl ist wie Du siehst keine mehr.";
  $s_teilsperrung_scvoll = "Leider sind bereits alle Spielerpl&auml;tze belegt. Bitte komm als NSC! :)";
  $s_teilsperrung_scvoll2 = "Leider sind bereits alle Spielerpl&auml;tze belegt.<br>Bitte komm als NSC! :)";
  $s_teilsperrung_nscvoll = "Leider sind bereits alle NSC-Pl&auml;tze belegt. Bitte komm als Spieler! :)";
  $s_teilsperrung_nscvoll2 = "Leider sind bereits alle NSC-Pl&auml;tze belegt.<br>Bitte komm als Spieler! :)";

  $s_menschentest_ueberschrift = "Menschlichkeitstest";
  $s_menschentest_1 = "Leider sind wir gezwungen diesen Test einzubauen, um automatische Programme (&quot;Bots&quot;) davon abzuhalten, sich anzumelden. Bitte schreibe in das Feld unten rein, was Du auf dem Bild darunter f&uuml;r ein Tier siehst. Wenn Du unsicher bist, welches Tier dargestellt ist, schau Dir";
  $s_menschentest_2 = "diese Liste</a> an, dort sind u.a. alle verwendeten Tiere zu finden.";
  $s_menschentest_3 = "Dieses Tier sehe ich:<br>(ohne Artikel, also nur z.B. &quot;Fisch&quot;)";

  $s_sonderfelder_ueberschrift = "Spezielle Fragen zu diesem Con";

  $s_teilnehmerdaten_ueberschrift = "Teilnehmerdaten";
  $s_teilnehmerdaten_vorname = "Vorname";
  $s_teilnehmerdaten_nachname = "Nachname";
  $s_teilnehmerdaten_anschrift = "Stra&szlig;e und Hausnummer";
  $s_teilnehmerdaten_plz = "PLZ";
  $s_teilnehmerdaten_ort = "Ort";
  $s_teilnehmerdaten_geburt = "Geburtsdatum";  
  $s_teilnehmerdaten_geschlecht = "Geschlecht";
  $s_teilnehmerdaten_geschlecht_k = "keine Angabe";
  $s_teilnehmerdaten_geschlecht_m = "M&auml;nnlich";
  $s_teilnehmerdaten_geschlecht_w = "Weiblich";
  $s_teilnehmerdaten_geschlecht_d = "Divers";
  $s_teilnehmerdaten_schwanger = "Schwanger";
  $s_teilnehmerdaten_essgewohnheiten = "Besondere Essgewohnheiten";
  $s_teilnehmerdaten_essgewohnheiten2 = "(Bitte genau eintragen welche Speisen Du nicht isst.)";
  $s_teilnehmerdaten_medausbildung = "Medizinische Ausbildung";
  $s_teilnehmerdaten_medausbildung_arzt = "Arzt";
  $s_teilnehmerdaten_medausbildung_rettungsassi = "Rettungsassistent";
  $s_teilnehmerdaten_medausbildung_mediziner = "Mediziner / AIP";
  $s_teilnehmerdaten_medausbildung_sani = "Sanit&auml;ter";
  $s_teilnehmerdaten_medausbildung_ersthelfer = "Ersthelfer";
  $s_teilnehmerdaten_medausbildung_keine = "keine";
  $s_teilnehmerdaten_medausbildung_wirmeldenuns = "(* wir melden uns bei Dir)";
  $s_teilnehmerdaten_telefon = "Telefon";
  $s_teilnehmerdaten_handy = "Handy";
  $s_teilnehmerdaten_email = "Email";

  $s_teilnehmerdaten_email2 = "(Dir wird ein Best&auml;tigungslink an diese Adresse gesendet, bitte achte auf Richtigkeit. Jede Email-Adresse kann sich nur einmal anmelden, daher benutze bitte Deine eigene Adresse)";
  $s_teilnehmerdaten_email3 = "(Jede Email-Adresse kann nur einmal f&uuml;r die Anmeldung genutzt werden.)";
  $s_teilnehmerdaten_email_nochmal = "Email (nochmal zur Kontrolle)";

  $s_teilnehmerdaten_sc = "SC";
  $s_teilnehmerdaten_nsc = "NSC";
  $s_teilnehmerdaten_spieler = "Spieler";
  $s_teilnehmerdaten_waehlen = "Bitte w&auml;hlen";
  $s_teilnehmerdaten_allergien = "Allergien";
  $s_teilnehmerdaten_allergien2 = "(Auch wenns doppelt scheint, bitte alles eintragen. So finden wir im Notfall alles schneller.)";
  $s_teilnehmerdaten_krankheiten = "Krankheiten";
  $s_teilnehmerdaten_phobien = "Phobien";
  $s_teilnehmerdaten_pflichtangaben = "Die mit * gekennzeichneten Felder m&uuml;ssen angegeben werden, damit eine Anmeldung m&ouml;glich ist.";

  $s_diverses_ueberschrift = "Diverses";
  $s_diverses_bemerkungen = "Bemerkungen, Erg&auml;nzungen, Unterbringungsw&uuml;nsche oder was Du uns sonst mitteilen willst";
  $s_diverses_wiedraufgekommen = "Ganz kurz nur: Wie hast Du von unserem Con erfahren?";
  $s_diverses_kennzeichen = "Bei Autoanreise: Kennzeichnen (<i>nur</i> der Fahrer, bitte!)";
  $s_diverses_stimmungsbild = "Kurzes Stimmungsbild: Was ist Dir am wichtigsten?";
  $s_diverses_stimmung_k = "K&auml;mpfen";
  $s_diverses_stimmung_p = "Plotten";
  $s_diverses_stimmung_z = "Zaubern";

  $s_anmeldebogen_ueberschrift = "Online-Anmeldung";
  $s_anmeldebogen_flyersehen = "Flyer ansehen";

  $s_abschluss_ueberschrift = "Rechtliches und Abschlie&szlig;endes";
  $s_abschluss_agb1 = "Ich habe die";
  $s_abschluss_agb2 = "Allgemeinen Gesch&auml;ftsbedingungen</a> gelesen und bin mit ihnen einverstanden.";
  $s_abschluss_agb_j = "Ja";
  $s_abschluss_agb_n = "Nein";
  $s_abschluss_agb3 = "Das Akzeptieren der AGB ist Vorraussetzung f&uuml;r die Teilnahme an dieser Veranstaltung.";
  $s_abschluss_mailspeichern1 = "Alle hier angegebenen Daten dienen dem Zweck die LARP Veranstaltung zu organisieren und werden nach dem Con gel&ouml;scht. Bitte lies unsere <a href='http://westliches-siegel.de/articles.php?article_id=74' target='_new'>Datenschutzerkl&auml;rung</a>.";
  $s_abschluss_mailspeichern2 = "Ich erkläre ich mich einverstanden, dass meine Daten zu diesen Zweck gespeichert und verarbeitet werden und dass mir Emails bezüglich meiner Anmeldung geschickt werden.";
  $s_abschluss_mailspeichern_j = "Ja, bitte speichern und Email senden";
  $s_abschluss_mailspeichern_n = "Nein, ich möchte mich lieber doch nicht anmelden.";
  $s_abschluss_flyerverweis = "Preise, Bankverbindungen, Kontaktadressen und weitere Informationen findest Du im aktuellen";
  $s_abschluss_flyer = "Flyer";
  $s_abschluss_beifragen = "Bei Fragen zur Anmeldung wende Dich an:";
  $s_abschluss_anmeldverbind = "Die Anmeldung ist verbindlich nachdem Du den Best&auml;tigungslink in Deiner Email angeklickt und den Conbeitrag &uuml;berwiesen hast!";
  $s_abschluss_kurzdruck = "(Bitte nur einmal dr&uuml;cken und kurz auf Best&auml;tigung warten)";

  $s_freischaltung_idfehler_title = "ID nicht gefunden";
  $s_freischaltung_idfehler_content = "Es konnte keine Anmeldung mit dieser ID in der Datenbank gefunden werden. Sollte der Fehler wiederholt auftreten, informiere bitte den Administrator.";
  $s_freischaltung_dbfehler_title = "Fehler";
  $s_freischaltung_dbfehler_content = "Deine Freischaltung konnte nicht in die Datenbank eingetragen werden. Sollte der Fehler wiederholt auftreten, informiere bitte den Administrator.";
  $s_freischaltung_ok_title = "Anmeldung vollst&auml;ndig";
  $s_freischaltung_ok_content1 = "Vielen Dank,";
  $s_freischaltung_ok_content2 = ".<br><br>Deine Email-Adresse ist best&auml;tigt und Du bist somit zum";
  $s_freischaltung_ok_content3 = "angemeldet.<br><b>Deine Anmeldung ist verbindlich, nachdem der Conbeitrag bei uns eingegangen ist.</b><br>Du erh&auml;lst in den n&auml;chsten Tagen eine Best&auml;tigung.<br><br>Preise, Bankverbindungen, Kontaktadressen und weitere Informationen findest Du im aktuellen ";
  $s_freischaltung_ok_content4 = "Vielen Dank und sp&auml;testens bis zum Con.<br><br>Deine<br>Westorga";
  $s_freischaltung_bereitsfrei_title = "Bereits freigeschaltet";
  $s_freischaltung_bereitsfrei_content = "Deine Anmeldung wurde bereits freigeschaltet. Solltest Du Fragen an uns haben, schreib uns doch eine Email an:";
  $s_freischaltung_codefalsch_title = "Code falsch";
  $s_freischaltung_codefalsch_content = "Dein eingegebener Code zur Email-Best&auml;tigung war nicht richtig. Vielleicht hast Du beim Kopieren des Links ein paar Zeichen vergessen? Solltest Du weiterhin Probleme damit haben, kontaktiere bitte die Orga:";
  $s_freischaltung_idnichtgefunden_title = "ID nicht gefunden";
  $s_freischaltung_idnichtgefunden_content = "Es konnte keine Veranstaltung mit dieser ID in der Datenbank gefunden werden. Sollte der Fehler wiederholt auftreten, informiere bitte den Administrator.";

  $s_anmeldefehler_menschentest = "Der Menschlichkeitstest (Tierbild erkennen) wurde nicht bestanden. Bitte versuche es mit einem neuen Tier nochmal.";
  $s_anmeldefehler_vorname = "Der Vorname wurde nicht ausgef&uuml;llt.";
  $s_anmeldefehler_nachname = "Der Nachname wurde nicht ausgef&uuml;llt.";
  $s_anmeldefehler_strasse = "Der Stra&szlig;enname wurde nicht ausgef&uuml;llt.";
  $s_anmeldefehler_plz = "Die Postleitzahl wurde nicht ausgef&uuml;llt.";
  $s_anmeldefehler_wohnort = "Der Wohnort wurde nicht ausgef&uuml;llt.";
  $s_anmeldefehler_geburtsjahr = "Das Geburtsjahr wurde nicht ausgef&uuml;llt.";
  $s_anmeldefehler_email_bereitsreg = "Diese Email-Adresse ist bereits angemeldet.";
  $s_anmeldefehler_email_leer = "Die Email-Adresse wurde nicht ausgef&uuml;llt.";
  $s_anmeldefehler_email_nochmal_leer = "Das Kontrollfeld f&uuml;r die Email-Adressen wurde nicht ausgef&uuml;llt.";
  $s_anmeldefehler_email_nochmal_falsch = "Die beiden Email-Adressen waren nicht gleich.";
  $s_anmeldefehler_agb = "Die AGB wurden nicht akzeptiert.";  
  $s_anmeldefehler_mailspeichern = "Der Speicherung der Daten wurde nicht zugestimmt.";
  $s_anmeldefehler_scnsc = "Es wurde nicht gew&auml;hlt, ob Spieler- oder NSC-Anmeldung gew&uuml;nscht ist.";
  $s_anmeldefehler_hinweistext = "Es gab ein Problem mit der Anmeldung";
  $s_anmeldefehler_ende = "Bitte korrigiere die Eingaben, damit die Anmeldung abgeschickt werden kann.";

//##################################################################################################
//###########   index.php   ########################################################################
//##################################################################################################

  $s_index_title = "Willkommen beim Consystem des westlichen Siegels";
  $s_index_introtext = "Diese Seiten geh&ouml;ren zum Converwaltungs-System des westlichen Siegels. Hier gibt es Infos zu den Cons der Westkampagne und die M&ouml;glichkeit sich zu Cons der Westorga online anzumelden.";
  $s_index_veranst_laeuft = "Laufende Veranstaltung";
  $s_index_veranst_aktuell = "Aktuelle Veranstaltung";
  $s_index_veranst_keine = "Keine aktuelle Veranstaltung";
  $s_index_datestring = "d.m.Y";
  $s_index_mysqldatestring = "%d.%m.%Y";

  $s_index_scplaetze = "SC-Pl&auml;tze frei";
  $s_index_nscplaetze = "NSC-Pl&auml;tze frei";
  $s_index_stand = "Stand"; //im Sinne von "Anmeldestand vom 12.12.2005"
  $s_index_zumflyer = "Zum Flyer";
  $s_index_zuranmeldung = "Zur Online-Anmeldung";
  $s_index_zumforum = "Zum Forum";

  $s_countdown_noch = "Noch"; //Noch 5 Tage, 20 Stunden ...
  $s_countdown_dps = "e";
  $s_countdown_hps = "n";
  $s_countdown_mps = "n";
  $s_countdown_sps = "n";
  $s_countdown_d = "Tag";
  $s_countdown_h = "Stunde";
  $s_countdown_m = "Minute";
  $s_countdown_s = "Sekunde";

//##################################################################################################
//###########   hpanmeldung.php   ##################################################################
//##################################################################################################

  $s_hpanmeldung_title = "Anmeldung";
  $s_hpanmeldung_orgalogin = "Orga Login";
  $s_hpanmeldung_explain = "Anmeldung f&uuml;r die Orga und SL zu Verwaltungszwecken";
  $s_hpanmeldung_lostpw = "Ich habe mein Passwort vergessen";
  $s_hpanmeldung_mail = "Email";
  $s_hpanmeldung_pw = "Passwort";

//##################################################################################################
//###########   western_design.php   ###############################################################
//##################################################################################################

  $s_design_menu_title = "Men&uuml;";
  $s_design_menu_nav = "Navigation";
  $s_design_menu_hp = "Consystem Startseite";
  $s_design_menu_back = "Zur&uuml;ck zur Webseite";
  $s_design_menu_forum = "Forum";
  $s_design_menu_datenschutzerklaerung = "Datenschutzerkl&auml;ung";
  $s_design_menu_impressum = "Impressum";
  $s_design_menu_orga = "Orgabereich";
  $s_design_menu_login = "Login";
  $s_design_easteregg = "Willkommen in der Halle der Nyame und des Archons in Grian Quihenya";

//##################################################################################################
//###########   tierliste.php   ####################################################################
//##################################################################################################

  $s_tierliste_title = "Tierliste f&uuml;r Menschlichkeitstest";
  $s_tierliste_explainer = "Hier eine Auswahl von Tieren f&uuml;r den &quot;Menschlichkeitstest&quot;. Damit es nicht zu leicht zu umgehen ist, sind diverse Tiere aufgef&uuml;hrt, die nicht auf Bildern erscheinen.";

  //WICHTIG: Diese ersten 10 >exakt< so wie oben uebersetzen, nur mit grossem Anfangsbuchstaben
  $s_tierliste_schmetterling = "Schmetterling";
  $s_tierliste_affe = "Affe";
  $s_tierliste_drache = "Drache";
  $s_tierliste_hund = "Hund";
  $s_tierliste_igel = "Igel";
  $s_tierliste_katze = "Katze";
  $s_tierliste_fuchs = "Fuchs";
  $s_tierliste_maus = "Maus";
  $s_tierliste_pferd = "Pferd";
  $s_tierliste_vogel = "Vogel";
  //------
  $s_tierliste_einhorn = "Einhorn";
  $s_tierliste_dachs = "Dachs";
  $s_tierliste_frosch = "Frosch";
  $s_tierliste_tiger = "Tiger";
  $s_tierliste_biber = "Biber";
  $s_tierliste_schlange = "Schlange";
  $s_tierliste_zergling = "zergling";
  $s_tierliste_muecke = "M&uuml;cke";
  $s_tierliste_regenwurm = "Regenwurm";
  $s_tierliste_hoellenhund = "H&ouml;llenhund";
  $s_tierliste_nilpferd = "Nilpferd";
  $s_tierliste_baer = "B&auml;r";
  $s_tierliste_faultier = "Faultier";
  $s_tierliste_nashorn = "Nashorn";
  $s_tierliste_hamster = "Hamster";
  $s_tierliste_kaefer = "K&auml;fer";
  $s_tierliste_fisch = "Fisch";
}

?>