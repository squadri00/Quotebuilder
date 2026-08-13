<?php 
session_cache_limiter('none');
session_start();
ob_start();
//include "vsadmin/db_conn_open.php";
//include "vsadmin/inc/languagefile.php";
//include "vsadmin/includes.php";
//include "vsadmin/inc/incfunctions.php";
//include "vsadmin/inc/metainfo.php";
include "config.php";
$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
//$url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; do not uncomment this line
$url = $_SERVER['HTTP_HOST'].'/';
//echo $url; do not uncomment this line
?><!DOCTYPE html>
<html  >
<head>
  
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
  <link rel="shortcut icon" href="assets/images/runwrk20logo201-413x100.png" type="image/x-icon">
  <meta name="description" content="Understanding your privacy is crucial. Explore our privacy policy to know how we safeguard your information.">
  
  
  <title>Privacy - Our Commitment to Privacy - Read Our Privacy Policy</title>
  <link rel="stylesheet" href="assets/web/assets/mobirise-icons2/mobirise2.css">
  <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/animatecss/animate.css">
  <link rel="stylesheet" href="assets/dropdown/css/style.css">
  <link rel="stylesheet" href="assets/socicon/css/styles.css">
  <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
  <link rel="preload" as="style" href="assets/mobirise/css/mbr-additional.css?v=e9rFrK"><link rel="stylesheet" href="assets/mobirise/css/mbr-additional.css?v=e9rFrK" type="text/css">

  
  
  <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TCBDSF5B"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<meta name="theme-color" content="#393193">
<link rel="manifest" href="manifest.json">
<script src="sw-connect.js"></script>
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<link rel="apple-touch-startup-image" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)" href="assets/images/apple-launch-640x1136.png">
<link rel="apple-touch-startup-image" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)" href="assets/images/apple-launch-750x1334.png">
<link rel="apple-touch-startup-image" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="assets/images/apple-launch-1242x2208.png">
<link rel="apple-touch-startup-image" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="assets/images/apple-launch-1125x2436.png">
<link rel="apple-touch-startup-image" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="assets/images/apple-launch-1170x2532.png">
<link rel="apple-touch-startup-image" media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="assets/images/apple-launch-1179x2556.png">
<link rel="apple-touch-startup-image" media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="assets/images/apple-launch-1290x2796.png">
<link rel="apple-touch-startup-image" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)" href="assets/images/apple-launch-1536x2048.png">
<link rel="apple-touch-startup-image" media="(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)" href="assets/images/apple-launch-1668x2224.png">
<link rel="apple-touch-startup-image" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)" href="assets/images/apple-launch-1668x2388.png">
<link rel="apple-touch-startup-image" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)" href="assets/images/apple-launch-2048x2732.png">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="">
<link rel="apple-touch-icon" href="apple-touch-icon.png"></head>
<body>

<!-- Analytics -->
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-GMYW11JP0V"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-GMYW11JP0V');
</script>

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-TCBDSF5B');</script>
<!-- End Google Tag Manager -->
<!-- /Analytics -->


  
  <section data-bs-version="5.1" class="menu menu2 cid-voK1J1D6Gd" once="menu" id="menu2-m1">
    
    <nav class="navbar navbar-dropdown navbar-fixed-top navbar-expand-lg">
        <div class="container">
            <div class="navbar-brand">
                <span class="navbar-logo">
                    <a href="index.html">
                        <img src="assets/images/runwrk20logo201-413x100.webp" alt="" style="height: 3rem;">
                    </a>
                </span>
                
            </div>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-bs-toggle="collapse" data-target="#navbarSupportedContent" data-bs-target="#navbarSupportedContent" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <div class="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav nav-dropdown" data-app-modern-menu="true"><li class="nav-item"><a class="nav-link link text-info text-primary display-4" href="features.html"><span class="mobi-mbri mobi-mbri-smile-face mbr-iconfont mbr-iconfont-btn"></span>Features</a>
                    </li><li class="nav-item"><a class="nav-link link text-info text-primary display-4" href="pricing.php"><span class="mobi-mbri mobi-mbri-idea mbr-iconfont mbr-iconfont-btn"></span>Pricing</a></li><li class="nav-item"><a class="nav-link link text-info text-primary display-4" href="page8.html"><span class="mobi-mbri mobi-mbri-globe-2 mbr-iconfont mbr-iconfont-btn"></span>Contact</a></li><li class="nav-item"><a class="nav-link link text-info text-primary display-4" href="https://app.meccora.com"><span class="mobi-mbri mobi-mbri-user-2 mbr-iconfont mbr-iconfont-btn"></span>Login</a></li></ul>
                
                <div class="navbar-buttons mbr-section-btn"><a class="btn btn-primary display-4" href="#">+1 (866) 798-7860</a></div>
            </div>
        </div>
    </nav>
</section>

<section data-bs-version="5.1" class="article12 cid-tZBaVJbP5C" id="article12-7m">    
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12 col-lg-10">
                		<h2 class="font-weight-bold font-weight-extra-bold line-height-1 text-left text-10 mt-0 mb-2 appear-animation" data-appear-animation="fadeInUpShorter" data-appear-animation-delay="250" data-appear-animation-duration="750">
						<?= $company_name; ?> Privacy Policy </h2>
						
						
						<?php $date = date('F Y', strtotime('-60 day')); ?>
						
						<h2><span class="text-3">Last updated on <?= $date; ?> </span></h2>
					 
					
					<h3>INTRODUCTION</h3>
					
					<p><?= $company_name; ?> respects the privacy of our users. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website <?= $website; ?> and our mobile application, including any other media form, media channel, mobile website, or mobile application related or connected thereto (collectively, the “<?= $website; ?>”). Please read this privacy policy carefully.  If you do not agree with the terms of this privacy policy, please do not access the site. </p>
					
					<p>We reserve the right to make changes to this Privacy Policy at any time and for any reason.  We will alert you about any changes by updating the “Last Updated” date of this Privacy Policy.  Any changes or modifications will be effective immediately upon posting the updated Privacy Policy on the Site, and you waive the right to receive specific notice of each such change or modification. </p>
					
					<p>You are encouraged to periodically review this Privacy Policy to stay informed of updates. You will be deemed to have been made aware of, will be subject to, and will be deemed to have accepted the changes in any revised Privacy Policy by your continued use of the Site after the date such revised Privacy Policy is posted.  </p>
					
					<h3>COLLECTION OF YOUR INFORMATION</h3>
					
					<p>We may collect information about you in a variety of ways. The information we may collect on the Site includes:</p>
					
					<h4>Personal Data </h4>
					<p>Personally identifiable information, such as your name, shipping address, email address, and telephone number, and demographic information, such as your age, gender, hometown, and interests, that you voluntarily give to us when you register with the Site or our mobile application, or when you choose to participate in various activities related to the Site and our mobile application, such as online chat and message boards. You are under no obligation to provide us with personal information of any kind, however your refusal to do so may prevent you from using certain features of the Site and our mobile application.</p>
					
					<h4>Derivative Data </h4>
					<p>Information our servers automatically collect when you access the Site, such as your IP address, your browser type, your operating system, your access times, and the pages you have viewed directly before and after accessing the Site. If you are using our mobile application, this information may also include your device name and type, your operating system, your phone number, your country, your likes and replies to a post, and other interactions with the application and other users via server log files, as well as any other information you choose to provide.</p>
					
					<h4>Financial Data </h4>
					<p>Financial information, such as data related to your payment method (e.g. valid credit card number, card brand, expiration date) that we may collect when you purchase, order, return, exchange, or request information about our services from the Site or our mobile application. [We store only very limited, if any, financial information that we collect. Otherwise, all financial information is stored by our payment processor, <?= $payment_processor1; ?> <?= $payment_processor2; ?> <?= $payment_processor3; ?> <?= $payment_processor4; ?> and you are encouraged to review their privacy policy and contact them directly for responses to your questions.</p>
					
					
					<h4>Facebook Permissions  </h4>
					<p>The Site [and our mobile application] may by default access your Facebook basic account information, including your name, email, gender, birthday, current city, and profile picture URL, as well as other information that you choose to make public. We may also request access to other permissions related to your account, such as friends, checkins, and likes, and you may choose to grant or deny us access to each individual permission. For more information regarding Facebook permissions, refer to the Facebook Permissions Reference page.</p>
					
					<h4>Data From Social Networks </h4>
					
					<p>User information from social networking sites, such as [Apple’s Game Center, Facebook, Google+, Instagram, Pinterest, Twitter], including your name, your social network username, location, gender, birth date, email address, profile picture, and public data for contacts, if you connect your account to such social networks. If you are using our mobile application, this information may also include the contact information of anyone you invite to use and/or join our mobile application.</p>
					
					<h4>Mobile Device Data </h4>
					<p>Device information, such as your mobile device ID, model, and manufacturer, and information about the location of your device, if you access the Site from a mobile device.</p>
					
					<h4>Third-Party Data </h4>
					<p>Information from third parties, such as personal information or network friends, if you connect your account to the third party and  grant the Site permission to access this information.</p>
					
					<h4>Data From Contests, Giveaways, and Surveys </h4>
					<p>Personal and other information you may provide when entering contests or giveaways and/or responding to surveys.</p>
					
					<h4>Mobile Application Information</h4>
					<p>If you connect using our mobile application:</p>
					<ul>
					<li>Geo-Location Information. We may request access or permission to and track location-based information from your mobile device, either continuously or while you are using our mobile application, to provide location-based services. If you wish to change our access or permissions, you may do so in your device’s settings.</li>
					<li>Mobile Device Access. We may request access or permission to certain features from your mobile device, including your mobile device’s [bluetooth, calendar, camera, contacts, microphone, reminders, sensors, SMS messages, social media accounts, storage,] and other features. If you wish to change our access or permissions, you may do so in your device’s settings.</li>
					<li>Mobile Device Data. We may collect device information (such as your mobile device ID, model and manufacturer), operating system, version information and IP address.</li>
					<li>Push Notifications. We may request to send you push notifications regarding your account or the Application. If you wish to opt-out from receiving these types of communications, you may turn them off in your device’s settings.</li>
					</ul>
					
					<h4>USE OF YOUR INFORMATION </h4>
					
					<p>Having accurate information about you permits us to provide you with a smooth, efficient, and customized experience.  Specifically, we may use information collected about you via the Site or our mobile application to: </p>
					
					
					<ul>
						<li>Administer sweepstakes, promotions, and contests.</li> 
						<li>Assist law enforcement and respond to subpoena.</li> 
						<li>Compile anonymous statistical data and analysis for use internally or with third parties. </li> 
						<li>Create and manage your account.</li> 
						<li>Deliver targeted advertising, coupons, newsletters, and other information regarding promotions and the Site [and our mobile application] to you. </li> 
						<li>Email you regarding your account or order.</li>
						<li>Enable user-to-user communications.</li>
						<li>Fulfill and manage purchases, orders, payments, and other transactions related to the Site [and our mobile application].</li>
						<li>Generate a personal profile about you to make future visits to the Site [and our mobile application] more personalized.</li>
						<li>Increase the efficiency and operation of the Site [and our mobile application].</li>
						<li>Monitor and analyze usage and trends to improve your experience with the Site [and our mobile application].</li>
						<li>Notify you of updates to the Site [and our mobile application]s.</li>
						<li>Offer new products, services, [mobile applications,] and/or recommendations to you.</li>
						<li>Perform other business activities as needed.</li>
						<li>Prevent fraudulent transactions, monitor against theft, and protect against criminal activity.</li>
						<li>Process payments and refunds.</li>
						<li>Request feedback and contact you about your use of the Site [and our mobile application] . </li>
						<li>Resolve disputes and troubleshoot problems.</li>
						<li>Respond to product and customer service requests.</li>
						<li>Send you a newsletter.</li>
						<li>Solicit support for the Site  [and our mobile application].</li>
						<li>[Other]</li>
					</ul>
					
					<h4>DISCLOSURE OF YOUR INFORMATION</h4>
					<p>We may share information we have collected about you in certain situations. Your information may be disclosed as follows:  </p>
					
					<h4>By Law or to Protect Rights</h4>
					<p>If we believe the release of information about you is necessary to respond to legal process, to investigate or remedy potential violations of our policies, or to protect the rights, property, and safety of others, we may share your information as permitted or required by any applicable law, rule, or regulation.  This includes exchanging information with other entities for fraud protection and credit risk reduction.</p>
					
					<h4>Third-Party Service Providers </h4>
					<p>We may share your information with third parties that perform services for us or on our behalf, including payment processing, data analysis, email delivery, hosting services, customer service, and marketing assistance.  </p>
					
					<h4>Marketing Communications</h4>
					<p>With your consent, or with an opportunity for you to withdraw consent, we may share your information with third parties for marketing purposes, as permitted by law.</p>
					
					<h4>Interactions with Other Users </h4>
					<p>If you interact with other users of the Site and our mobile application, those users may see your name, profile photo, and descriptions of your activity, including sending invitations to other users, chatting with other users, liking posts, following blogs.</p> 
					
					<h4>Online Postings</h4>
					<p>When you post comments, contributions or other content to the Site [or our mobile applications], your posts may be viewed by all users and may be publicly distributed outside the Site [and our mobile application] in perpetuity. </p>
					
					<h4>Third-Party Advertisers </h4>
					<p>We may use third-party advertising companies to serve ads when you visit the Site or our mobile application. These companies may use information about your visits to the Site and our mobile application and other websites that are contained in web cookies in order to provide advertisements about goods and services of interest to you.</p> 
					
					<h4>Affiliates </h4>
					<p>We may share your information with our affiliates, in which case we will require those affiliates to honor this Privacy Policy. Affiliates include our parent company and any subsidiaries, joint venture partners or other companies that we control or that are under common control with us.</p>
					
					<h4>Business Partners </h4>
					<p>We may share your information with our business partners to offer you certain products, services or promotions. </p>
					
					<h4>Offer Wall  </h4>
					<p>Our mobile application may display a third-party hosted “offer wall.”  Such an offer wall allows third-party advertisers to offer virtual currency, gifts, or other items to users in return for acceptance and completion of an advertisement offer.  Such an offer wall may appear in our mobile application and be displayed to you based on certain data, such as your geographic area or demographic information.  When you click on an offer wall, you will leave our mobile application.  A unique identifier, such as your user ID, will be shared with the offer wall provider in order to prevent fraud and properly credit your account.</p>    
					
					<h4>Social Media Contacts  </h4>
					<p>If you connect to the Site or our mobile application through a social network, your contacts on the social network will see your name, profile photo, and descriptions of your activity.</p> 
					
					<h4>Other Third Parties</h4>
					<p>We may share your information with advertisers and investors for the purpose of conducting general business analysis. We may also share your information with such third parties for marketing purposes, as permitted by law.</p> 
					
					<h4>Sale or Bankruptcy </h4>
					<p>If we reorganize or sell all or a portion of our assets, undergo a merger, or are acquired by another entity, we may transfer your information to the successor entity.  If we go out of business or enter bankruptcy, your information would be an asset transferred or acquired by a third party.  You acknowledge that such transfers may occur and that the transferee may decline honor commitments we made in this Privacy Policy.</p>
					
					<p>We are not responsible for the actions of third parties with whom you share personal or sensitive data, and we have no authority to manage or control third-party solicitations.  If you no longer wish to receive correspondence, emails or other communications from third parties, you are responsible for contacting the third party directly.</p>

					<hr>
					<!-- End  -->
            </div>
        </div>
    </div>
</section>

<section data-bs-version="5.1" class="article12 cid-tZBRkpCIWx" id="website-cookies">    
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12 col-lg-10">
					
					<h4>TRACKING TECHNOLOGIES</h4>
					<p></p>
					<h4>Cookies and Web Beacons</h4>
					<p>We may use cookies, web beacons, tracking pixels, and other tracking technologies on the Site and our mobile application to help customize the Site and our mobile application and improve your experience. When you access the Site or our mobile application, your personal information is not collected through the use of tracking technology. Most browsers are set to accept cookies by default. You can remove or reject cookies, but be aware that such action could affect the availability and functionality of the Site  [or our mobile application]. You may not decline web beacons. However, they can be rendered ineffective by declining all cookies or by modifying your web browser’s settings to notify you each time a cookie is tendered, permitting you to accept or decline cookies on an individual basis.</p>
					
					<p>We may use cookies, web beacons, tracking pixels, and other tracking technologies on the Site and our mobile application to help customize the Site [and our mobile application] and improve your experience. For more information on how we use cookies, please refer to our Cookie Policy posted on the Site, which is incorporated into this Privacy Policy. By using the Site, you agree to be bound by our Cookie Policy.</p>
					
					<h4>Internet-Based Advertising</h4>
					<p>Additionally, we may use third-party software to serve ads on the Site [and our mobile application], implement email marketing campaigns, and manage other interactive marketing initiatives.  This third-party software may use cookies or similar tracking technology to help manage and optimize your online experience with us.  For more information about opting-out of interest-based ads, visit the Network Advertising Initiative Opt-Out Tool or Digital Advertising Alliance Opt-Out Tool.</p>
					
					<h4>Website Analytics </h4>
					<p>We may also partner with selected third-party vendors[, such as [Adobe Analytics,] [Clicktale,] [Clicky,] [Cloudfare,] [Crazy Egg,] [Flurry Analytics,] [Google Analytics,] [Heap Analytics,] [Inspectlet,] [Kissmetrics,] [Mixpanel,] [Piwik,] and others], to allow tracking technologies and remarketing services on the Site [and our mobile application] through the use of first party cookies and third-party cookies, to, among other things, analyze and track users’ use of the Site [and our mobile application] , determine the popularity of certain content and better understand online activity. By accessing the Site[,our mobile application,], you consent to the collection and use of your information by these third-party vendors. You are encouraged to review their privacy policy and contact them directly for responses to your questions. We do not transfer personal information to these third-party vendors. However, if you do not want any information to be collected and used by tracking technologies, you can visit the third-party vendor or the Network Advertising Initiative Opt-Out Tool or Digital Advertising Alliance Opt-Out Tool.</p>
					
					<p>You should be aware that getting a new computer, installing a new browser, upgrading an existing browser, or erasing or otherwise altering your browser’s cookies files may also clear certain opt-out cookies, plug-ins, or settings.</p>
					
					<h4>THIRD-PARTY WEBSITES</h4>
					
					<p>The Site and our mobile application may contain links to third-party websites and applications of interest, including advertisements and external services, that are not affiliated with us. Once you have used these links to leave the Site [or our mobile application], any information you provide to these third parties is not covered by this Privacy Policy, and we cannot guarantee the safety and privacy of your information. Before visiting and providing any information to any third-party websites, you should inform yourself of the privacy policies and practices (if any) of the third party responsible for that website, and should take those steps necessary to, in your discretion, protect the privacy of your information. We are not responsible for the content or privacy and security practices and policies of any third parties, including other sites, services or applications that may be linked to or from the Site [or our mobile application].</p>
					
					<h4>SECURITY OF YOUR INFORMATION</h4>
					
					<p>We use administrative, technical, and physical security measures to help protect your personal information.  While we have taken reasonable steps to secure the personal information you provide to us, please be aware that despite our efforts, no security measures are perfect or impenetrable, and no method of data transmission can be guaranteed against any interception or other type of misuse.  Any information disclosed online is vulnerable to interception and misuse by unauthorized parties. Therefore, we cannot guarantee complete security if you provide personal information.</p>
					
					<h4>POLICY FOR CHILDREN</h4>
					
					<p>We do not knowingly solicit information from or market to children under the age of 13. If you become aware of any data we have collected from children under age 13, please contact us using the contact information provided below. </p>
					
					<h4>CONTROLS FOR DO-NOT-TRACK FEATURES  </h4>
					
					<p>Most web browsers and some mobile operating systems [and our mobile applications] include a Do-Not-Track (“DNT”) feature or setting you can activate to signal your privacy preference not to have data about your online browsing activities monitored and collected.  No uniform technology standard for recognizing and implementing DNT signals has been finalized. As such, we do not currently respond to DNT browser signals or any other mechanism that automatically communicates your choice not to be tracked online.  If a standard for online tracking is adopted that we must follow in the future, we will inform you about that practice in a revised version of this Privacy Policy./Most web browsers and some mobile operating systems [and our mobile applications] include a Do-Not-Track (“DNT”) feature or setting you can activate to signal your privacy preference not to have data about your online browsing activities monitored and collected. If you set the DNT signal on your browser, we will respond to such DNT browser signals. </p>
					
					<h4>OPTIONS REGARDING YOUR INFORMATION</h4>
					
					<h5>[Account Information]</h5>
					<p>You may at any time review or change the information in your account or terminate your account by:</p>
					<ul>
					<li>Logging into your account settings and updating your account</li>
					<li>Contacting us using the contact information provided below</li>


					<li>[Other]</li>
					</ul>
					<p>Upon your request to terminate your account, we will deactivate or delete your account and information from our active databases. However, some information may be retained in our files to prevent fraud, troubleshoot problems, assist with any investigations, enforce our Terms of Use and/or comply with legal requirements.]</p>
					
					<h5>Emails and Communications</h5>
					<p>If you no longer wish to receive correspondence, emails, or other communications from us, you may opt-out by:</p>
					<ul>
					<li>Noting your preferences at the time you register your account with the Site [or our mobile application]</li>
					<li>Logging into your account settings and updating your preferences.</li>
					<li>Contacting us using the contact information provided below</li>
					</ul>
					<p>If you no longer wish to receive correspondence, emails, or other communications from third parties, you are responsible for contacting the third party directly. </p>
					
					
					<h4>CONTACT US</h4>
					
					<p>If you have questions or comments about this Privacy Policy, please <a href="https://eformics.com/contact.html">contact us </a></p>
					
					
					<!-- End  -->
            </div>
        </div>
    </div>
</section>

<section data-bs-version="5.1" class="footer02 politicsm4_footer02 cid-tZtcDa4dPo" once="footers" id="footer02-2u">

	

	

	<div class="container">
		<div class="row justify-content-center content text-white">
			<div class="col-9 col-md-12 col-lg-5">
				<h4 class="align-left mbr-fonts-style display-4">© Meccora.com by Eformics Systems - 2009 - 2026 All
					Rights Reserved</h4>
			</div>

			<div class="col-12 col-md-12 col-lg-7 mbr-flex">
				<div class="item">
					<div class="card-img"><span class="mbr-iconfont img1 mobi-mbri-pin mobi-mbri"></span>
					</div>
					<div class="card-box">
						<h4 class="item-title align-left mbr-fonts-style display-4"><a href="privacy-policy.html" class="text-white text-primary">Privacy Policy</a>
						</h4>
					</div>
				</div>

				<div class="item">
					<div class="card-img"><span class="mbr-iconfont img1 mobi-mbri-pin mobi-mbri"></span></div>
					<div class="card-box">
						<h4 class="item-title align-left mbr-fonts-style display-4"><a href="terms-and-conditions.html" class="text-white text-primary">Terms of Use</a>
						</h4>
					</div>
				</div>




			</div>
		</div>
	</div>
</section>


<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/smoothscroll/smooth-scroll.js"></script>
  <script src="assets/ytplayer/index.js"></script>
  <script src="assets/dropdown/js/navbar-dropdown.js"></script>
  <script src="assets/theme/js/script.js"></script>
  
                              <script>
                            // Check if the browser supports the beforeinstallprompt event
                            window.addEventListener('beforeinstallprompt', (event) => {
                                // Prevent the browser's default install prompt
                                event.preventDefault();

                                // Show your custom install button
                                document.getElementById('installButton').style.display = 'block';

                                // Handle the install button click
                                document.getElementById('installButton').addEventListener('click', () => {
                                // Trigger the install prompt
                                event.prompt();
                                // Wait for the user to respond to the prompt
                                event.userChoice.then((choiceResult) => {
                                    if (choiceResult.outcome === 'accepted') {
                                    console.log('User accepted the install prompt');
                                    } else {
                                    console.log('User dismissed the install prompt');
                                    }
                                });
                                });
                            });
                            </script>

<script type="text/javascript" src="https://chatterpal.me/build/js/chatpal.js?8.3" integrity="sha384-+YIWcPZjPZYuhrEm13vJJg76TIO/g7y5B14VE35zhQdrojfD9dPemo7q6vnH44FR" crossorigin="anonymous" data-cfasync="false"></script>

  
 <div id="scrollToTop" class="scrollToTop mbr-arrow-up"><a style="text-align: center;"><i class="mbr-arrow-up-icon mbr-arrow-up-icon-cm cm-icon cm-icon-smallarrow-up"></i></a></div>
    <input name="animation" type="hidden">
  </body>
</html>