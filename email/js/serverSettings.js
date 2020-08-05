/**
 * 
 * @returns
 */
function mailServerSettings() {
    var email = document.getElementById('email');
    var server = document.getElementById('server');
    var protocol = document.getElementById('protocol');
    var security = document.getElementById('security');
    var cert = document.getElementById('cert');
    var folder = document.getElementById('folder');
    var user = document.getElementById('user');
    var smtpServer = document.getElementById('smtpServer');
    var smtpSecure = document.getElementById('smtpSecure');
    var smtpAuth = document.getElementById('smtpAuth');
    var smtpUser = document.getElementById('smtpUser');

    var n = email.value.search("@");

    var provider = email.value.substr(n+1);
    var userAccountt = email.value.substr(0,n);

    if (server.value == "") {
		switch (provider) {
		    case "abv.bg":
		    	server.value = "pop3.abv.bg:995";
			    protocol.value = "pop3";
			    security.value = "ssl";
			    cert.value = "noValidate";
			    smtpServer.value = "smtp.abv.bg:465";
			    smtpSecure.value = "ssl";
			    smtpAuth.value = "LOGIN";
			    user.value = email.value;
			    smtpUser.value = email.value;
		    	break;
		    case "gmail.com":
		    	server.value = "imap.gmail.com:993";
		    	protocol.value = "imap";
		    	security.value = "ssl";
		    	cert.value = "validate";
		    	smtpServer.value = "smtp.gmail.com:587";
		    	smtpSecure.value = "tls";
		    	smtpAuth.value = "LOGIN";
		    	user.value = email.value;
		    	smtpUser.value = email.value;
		        break;
		    case "yahoo.com":
		    	server.value = "imap.mail.yahoo.com:993";
		    	protocol.value = "imap";
		    	security.value = "ssl";
		    	cert.value = "validate";
		    	smtpServer.value = "smtp.mail.yahoo.com:465";
		    	smtpSecure.value = "ssl";
		    	smtpAuth.value = "LOGIN";
		    	user.value = email.value;
		    	smtpUser.value = email.value;
		        break;
		    case "outlook.com":
		    	server.value = "imap-mail.outlook.com:993";
		    	protocol.value = "imap";
		    	security.value = "ssl";
		    	cert.value = "validate";
		    	smtpServer.value = "smtp-mail.outlook.com:587";
		    	smtpSecure.value = "tls";
		    	smtpAuth.value = "LOGIN";
		    	user.value = email.value;
		    	smtpUser.value = email.value;
		        break;
		    case "mail.bg":
		    	server.value = " imap.mail.bg:143";
		    	protocol.value = "imap";
		    	security.value = "tls";
		    	cert.value = "validate";
		    	smtpServer.value = "smtp.mail.bg:465";
		    	smtpSecure.value = "tls";
		    	smtpAuth.value = "LOGIN";
		    	user.value = email.value;
		    	smtpUser.value = email.value;
		        break;

		    default:
		    	protocol.value = "imap";
		    	security.value = "default";
		    	cert.value = "noValidate";
		    	smtpSecure.value = "no";
		    	smtpAuth.value = "no";
		}

        if($('.select2').length){
            $('select').trigger("change");
        }
    }
};
