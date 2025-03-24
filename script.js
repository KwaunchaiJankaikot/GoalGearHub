// รอให้หน้าเว็บโหลดเสร็จก่อน
document.addEventListener("DOMContentLoaded", function() {
    // เลือกทุกไอคอนที่มี class 'favorite-icon'
    var favoriteIcons = document.querySelectorAll(".favorite-icon");

    // สำหรับแต่ละไอคอน
    favoriteIcons.forEach(function(icon) {
        // ดึงค่า product ID จาก data attribute
        var productId = icon.getAttribute("data-product-id");

        // ตรวจสอบสถานะการถูกใจเมื่อโหลดหน้าเว็บ
        checkFavoriteStatus(productId, icon);

        // เพิ่ม Event Listener เพื่อจับการคลิก
        icon.addEventListener("click", function() {
            toggleFavorite(icon, productId); // เรียกฟังก์ชันเมื่อไอคอนถูกคลิก
        });
    });
});

// ฟังก์ชันเพื่อเพิ่มหรือลบสินค้าในรายการโปรด
function toggleFavorite(icon, productId) {
    var action = icon.classList.contains("active") ? "remove" : "add"; // ตรวจสอบว่าจะเพิ่มหรือลบ

    var xhr = new XMLHttpRequest(); // สร้าง XMLHttpRequest
    xhr.open("POST", "favorite.php", true); // เปิดการเชื่อมต่อแบบ POST ไปยัง favorite.php
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded"); // ตั้งค่า header

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) { // ตรวจสอบสถานะการตอบสนอง
            try {
                var response = JSON.parse(xhr.responseText); // ตรวจสอบว่าได้รับข้อมูลที่เป็น JSON หรือไม่
                if (response.status === 'success') {
                    if (action === "add") {
                        icon.classList.add("active"); // ถ้าเพิ่ม ให้เพิ่ม class 'active'
                    } else {
                        icon.classList.remove("active"); // ถ้าลบ ให้ลบ class 'active'
                    }
                    alert(response.message); // แสดงข้อความตอบสนองที่กำหนด
                } else {
                    alert('Unexpected response format: ' + response.message);
                }
            } catch (e) {
                alert('Error parsing response: ' + e.message);
            }
        }
    };

    // ส่งข้อมูลไปยัง PHP พร้อมกับ action ที่ถูกต้อง (add/remove)
    xhr.send("product_id=" + encodeURIComponent(productId) + "&action=" + encodeURIComponent(action));
}

// ฟังก์ชันเพื่อตรวจสอบสถานะการถูกใจ
function checkFavoriteStatus(productId, icon) {
    var xhr = new XMLHttpRequest(); // สร้าง XMLHttpRequest
    xhr.open("GET", "favorite_status.php?product_id=" + encodeURIComponent(productId), true); // เปิดการเชื่อมต่อแบบ GET ไปยัง favorite_status.php

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) { // ตรวจสอบสถานะการตอบสนอง
            try {
                var response = JSON.parse(xhr.responseText); // ตรวจสอบว่าได้รับข้อมูลที่เป็น JSON หรือไม่
                if (response.status === 'favorited') { // ถ้าสินค้าอยู่ในรายการโปรด
                    icon.classList.add("active"); // เพิ่ม class 'active' เพื่อแสดงว่าเป็นสินค้าที่ถูกใจ
                }
            } catch (e) {
                console.error('Error parsing response: ' + e.message);
            }
        }
    };

    xhr.send(); // ส่งคำขอไปยัง PHP
}

// ฟังก์ชันเพื่อเปิด modal ที่แสดงรายการโปรด
function openFavoriteModal() {
    document.getElementById('favoriteModal').style.display = 'block'; // แสดง modal

    // โหลดรายการสินค้าโปรดจาก favorite.php
    fetch('favorite.php')
        .then(response => response.text()) // แปลงการตอบสนองเป็น text
        .then(data => {
            document.getElementById('favoriteItems').innerHTML = data; // แสดงรายการสินค้าโปรดใน modal
        })
        .catch(error => {
            console.error('Error fetching favorite items:', error);
            alert('Failed to load favorite items.');
        });
}

// ฟังก์ชันเพื่อปิด modal
function closeFavoriteModal() {
    document.getElementById('favoriteModal').style.display = 'none'; // ซ่อน modal
}
