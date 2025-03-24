<?php if (session_status() == PHP_SESSION_NONE) {
    session_start();
}?>
<?php include_once('header.php'); ?>
<?php include_once('nav.php'); ?>

<style>
    .index h1 {
        text-align: center;
        text-shadow: 10px 10px #333333;
    }

    .shoe {
        width: 300px;
        height: 200px;
        margin-left: 10px;
    }

    .textshoe {
        margin-left: 10px;
        font-size: 24px;
    }

    .chelsea2 {
        width: 300px;
        height: 200px;
        margin-top: 0px;
        margin-left: 10px;
    }

    .textchelsea {
        margin-left: 10px;
        font-size: 24px;
    }

    .jesus {
        width: 300px;
        height: 550px;
        margin-top: -505px;
        margin-left: 400px;
        object-fit: fill;
        position: relative;
    }

    .textjesus {
        width: 105px;
        height: 40px;
        margin-top: -88px;
        margin-left: 410px;
        position: absolute;
        font-size: 35px;
    }

    .buttonjesus {
        margin-left: 410px;
        margin-top: -45px;
        width: 108px;
        height: 40px;
        position: absolute;
        background-color: #6D6D6D;
    }

    .casemiro {
        width: 300px;
        height: 550px;
        margin-top: -575px;
        margin-left: 750px;
        object-fit: fill;
    }

    .textcasemiro {
        width: 220px;
        height: 40px;
        margin-top: -100px;
        margin-left: 760px;
        position: absolute;
        font-size: 24px;
    }

    .buttoncasemiro {
        margin-left: 760px;
        margin-top: -68px;
        width: 108px;
        height: 40px;
        position: absolute;
        background-color: #6D6D6D;
    }

    .rashford {
        width: 300px;
        height: 550px;
        margin-top: -622px;
        margin-left: 1110px;
        object-fit: fill;
    }

    .textrashford {
        width: 60px;
        height: 40px;
        margin-top: -125px;
        margin-left: 1120px;
        position: absolute;
        font-size: 24px;
    }

    .buttonrashford {
        margin-left: 1120px;
        margin-top: -92px;
        width: 108px;
        height: 40px;
        position: absolute;
        background-color: #6D6D6D;
    }

    .brand {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-left: 35px;
    }
</style>

<div class="index">
    <br>
    <h1 class="brand"> สินค้าของเรา </h1>
    <br>

    <div>
        <a href="welcome.php"><img src="img/shoe.jpg" alt="shoe" class="shoe"></a>
        <p class="textshoe">รองเท้าฟุตบอล</p>
    </div>

    <div>
        <a href="welcome.php"><img src="img/chelsea2.jpg" alt="cheslseateam" class="chelsea2"></a>
        <p class="textchelsea">Chelsea</p>
    </div>

    <div>
        <a href="welcome.php"><img src="img/jesus.jpg" alt="jesus" class="jesus"></a>
        <p class="textjesus">adidas</p>

        <form action="welcome.php" method="get">
            <button type="submit " class=" btn btn-secondary buttonjesus">เลือกดูสินค้า</button>
        </form>
    </div>

    <div>
        <a href="welcome.php"><img src="img/casemiro.jpg" alt="casemiro" class="casemiro"></a>
        <p class="textcasemiro">Manchester United</p>
        <form action="welcome.php" method="get">
            <button type="submit " class=" btn btn-secondary buttoncasemiro">เลือกดูสินค้า</button>
        </form>
    </div>

    <div>
        <a href="welcome.php"><img src="img/rashford.jpg" alt="rashford" class="rashford"></a>
        <p class="textrashford">Nike</p>
        <form action="welcome.php" method="get">
            <button type="submit " class=" btn btn-secondary buttonrashford">เลือกดูสินค้า</button>
        </form>
    </div>
    
</div>

<?php include_once('footer.php'); ?>
