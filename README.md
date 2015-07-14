Tiny PHP service for single-page web apps.

This is an API that provides:

 * Simple Text-file based authentication.
 * Storage of authenticated client state in simple text files.
 * Simple proxying of ajax GET requests.

This is intended for small, self-hosted, single-page web apps with a handful of users. Cross-domain requests are permitted by default in the code. **It's probably full of security holes**. Don't use this for production websites with lots of users and don't store important data with this.

**Security notice**: Like many PHP applications, when first uploaded this API waits for input to create the first user. Until the first user is created and secured with a password, the API is wide open.

### Endpoints ###

Log a user in (if no user exists yet this call creates the first user with the specified password).

	/index.php&username=bob&password=hello

Proxy an ajax GET request via the server.

	/index.php?proxy=http://example.com/mysite.json

Write the current user's state to the server.

	/index.php?state=this+is+a+string+containing+my+state

Read the current user's state from the server.

	/index.php?state

Log the current user out, deleting their session.

	/index.php?logout

### Authentication ###

Username/password tokens are stored in a file called `users.txt`.

If the file does not exist, the server will return `AUTH_NO_FILE` and the client can send a request like `/index.php?username=bob&password=hello` to write the first user to the file - for purely web based installation/bootstrapping.

Format of the `users.txt` file is `username: encrypted-or-cleartext-password`, one per line.

To encrypt a password use the `htpasswd` utility or `crypt($pw, base64_encode($pw));` in PHP.

### Proxy ###

Results are JSON encoded. If the endpoint being requested is already JSON encoded then add `?json=true` to the request and it won't be double-encoded.

### User State ###

Each user's state is stored in a file called `USERNAME.txt` where USERNAME is the user's username.

### API Response Codes ###

 * `AUTH_NO_FILE` = `users.txt` file does not exist yet. Make a request with `username` and `password` parameters set to create the file.
 * `AUTH_FILE_CREATED` = Request with `username` and `password` has successfully resulted in the `users.txt` file being created.
 * `AUTH_NO_CREDENTIALS` = User is not logged in and no `username` and `password` parameters were passed.
 * `AUTH_FAILED` = `username` and `password` parameters were supplied but don't match any in the `users.txt` file.
 * `AUTHENTICATED` = When a user is successfully authenticated.
 * `AUTH_LOGGED_OUT` = When a request to `/auth.php?logout` is made and the user has successfully been logged out, session deleted.
 * `STATE_WRITTEN` = When a request to `/state.php?state=...` has successfully written the state to the user's state file.

