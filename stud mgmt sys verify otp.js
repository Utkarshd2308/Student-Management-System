const inputs = document.querySelectorAll(".otp-input");
    inputs.forEach((input, index) => {
        input.addEventListener("input", (e) => {
            if (e.target.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && !input.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });

    // Show mobile number from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const mobile = urlParams.get("mobile");
    if (mobile) {
        const masked = mobile.replace(/(\d{5})(\d{5})/, "$1*");
        document.getElementById("mobile-number").textContent = masked;
    } else {
        document.getElementById("mobile-number").textContent = "**";
    }
