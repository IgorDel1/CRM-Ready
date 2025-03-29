<?php 

function OutputProducts($products) {
    foreach ($products as $key => $product) {
        $id = $product['id'];
        $name = $product['name'];
        $description = $product['description'];
        $price = $product['price'];
        $stock = $product['stock'];
        echo "
            <tr>
                <td>$id</td>
                <td>$name</td>
                <td>$description</td>
                <td>$price</td>
                <td>$stock</td>
                <td><a href='api/product/generateQR.php?id=$id'><i class='fa fa-qrcode'></i></a></td>
                <td onclick=\"editProduct($id, '" . htmlspecialchars($name, ENT_QUOTES) . "', '" . htmlspecialchars($description, ENT_QUOTES) . "', $price, $stock)\"><i class='fa fa-pencil'></i></td>
                <td><a href='api/product/ProductDelete.php?id=$id'><i class='fa fa-trash'></i></a></td>
            </tr>";
    }
}

?>