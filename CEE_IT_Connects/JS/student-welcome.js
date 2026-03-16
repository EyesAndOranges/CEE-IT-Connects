document.addEventListener('DOMContentLoaded', () => {
    
    // Example: If you want to grab the name from localStorage (if set during register)
    // const userName = localStorage.getItem('userName');
    // if (userName) {
    //     document.getElementById('user-name').textContent = userName + "!";
    // }

});

function handleSkip() {
    // Logic for skipping customization
    alert("Skipping customization... Redirecting to Dashboard.");
    // window.location.href = "dashboard.html"; 
}

function handleProceed() {
    // Logic for proceeding to customization
    alert("Proceeding to Account Customization...");
    // window.location.href = "customize-account.html";
}