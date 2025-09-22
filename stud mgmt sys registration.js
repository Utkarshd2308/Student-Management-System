    function sendOTP() {
        const mobile = document.getElementById("mobileNo").value.trim();
        if (mobile === "" (mobile)) {
            alert("Please enter a valid 10-digit mobile number");
            return;
        }
        window.location.href = "stud mgmt sys verify otp.html?mobile=" + mobile;
    }