document.addEventListener("DOMContentLoaded", function () {
    const loginBtn = document.querySelector("button");
    const usernameInput = document.querySelector("input[placeholder='Enter UserName']");
    const passwordInput = document.querySelector("input[placeholder='Enter Password']");

    loginBtn.addEventListener("click", function () {
        const username = usernameInput.value.trim();
        const password = passwordInput.value.trim();

        if (username === "" || password === "") {
            alert("Please fill in both Username and Password.");
            return;
        }
        if (username === "admin" && password === "1234") {
            alert("Login Successful!");
        } else {
            alert("Invalid username or password.");
        }
    });
});