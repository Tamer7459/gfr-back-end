const http = require("http");

const data = JSON.stringify({
    name: "Test User",
    email: "test@example.com",
    password: "password123",
    password_confirmation: "password123",
});

const options = {
    hostname: "localhost",
    port: 8000,
    path: "/api/register",
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        "Content-Length": data.length,
    },
};

const req = http.request(options, (res) => {
    let body = "";
    res.on("data", (chunk) => {
        body += chunk;
    });
    res.on("end", () => {
        console.log("Status:", res.statusCode);
        console.log("Response:", body);
    });
});

req.on("error", (e) => {
    console.error("Error:", e.message);
});

req.write(data);
req.end();
