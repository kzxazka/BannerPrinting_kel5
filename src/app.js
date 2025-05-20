$(document).ready(function() {
    $('pay-button').click(function (e) {
        e.preventDefault();
        $.ajax({
            url: 'placeOrder.php',
            method: 'POST',
            data: {
                order_id: orderId
            },
            success: function (response) {
                snap.pay(response.snap_token, {
                    onSuccess: function(result) {
                        alert("Pembayaran berhasil!");
                        console.log(result);
                        window.location.href = "success.php";
                    },
                    onPending: function(result) {
                        alert("Pembayaran belum selesai.");
                        console.log(result);
                        window.location.href = "pending.php";
                    },
                    onError: function(result) {
                        alert("Pembayaran gagal!");
                        console.log(result);
                        window.location.href = "failed.php";
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert("Gagal mendapatkan token pembayaran!");
            }
        });
    });
});
