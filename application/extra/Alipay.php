<?php
return [
    //应用ID,您的APPID。
    'app_id' => "2018060160312456",
    //商户私钥
    'merchant_private_key' =>'MIIEogIBAAKCAQEAx12EwSq9FbRMELPR2EI8Vhg88G/ozpHTIzLhtuZLI87E0wEoh2eFiS08J8vOmmc9YJkEUBcdprchRmXsYdzmDHlrzmkWpCC2w6Cwh5bVAALAnd5LF8Oqzmy5UUu2V32GCAYTlCT0BDZLiQks3SBHHeFwKmbxfQC7oekvl+jcl8NiTd+WGhtxZCe8wcpNP2eTcRbR/PASlrurEvQ45rl0eNmzj7t+ksWQO7VVY+ADq2UMLfqILs+d4J3wLgGvqKMOV+AKONSZS66na3wGSyyi7d7K/+udlCSVQ50S4FzqDv0//dB4eU9XVxCfIYqhCqJ0gMqAlAJ5xK0PhCk+hQ87UwIDAQABAoIBAFg9dTfGQeCsZ4pw/K06S9hTbA2Dish7VLdcmvjIH3Pe3dECzzx3PmyD3/9BPtWqYkkyEO2d/Zq/rXOqQyDhk9TrnSLD8qh8bkhSBDBPO0GA9l4peJUxHpW1G6T23OMGhN9iSSIl5PdETNR/giLxWWjJUksCO1p3i5TTUCRthc2Jffn1zUHaf63uULi7c+E6emVoJ9FaOoZKzwl8CWhtt97ezoF2CFEdnCxZUBrYe5LfKmKYgpBnbjwdh1AyCRN3uESc3MYbNLx0Dm1Osto+wT3eBxMP4g1BA+H8ZqtU18oeHjMsFBdtTt68/7x/3F6c0H4z7zSXER52lBsVPfkLw0kCgYEA6+tO6/e1f38gcV3wu9f2/mM8RQQ9BxllciEGFNxjVmw/DtmnMjwP4OrDlo1/BCNFcNJgquM9cXhxRX81lYWE2meS3EuKsBmfl5oFoQUsU+xTEJ9pivuo4bko5rEWf9FRnoqGAGXJ36fxasJKHy4zgXgoKE0Je3FwyMAYckFzwfUCgYEA2FWzFPp3FosABD0hjUSKb3Dn4Ag9mMfSGjrYcuIFRgg9AsVFoCldew1dDxqBmIAPLjBkfJYsMI8URPaJTMFsBzDQfW1I06fWHLfC+0M3QGl9WhTmmjcX/Xi/V5Br0POv/NXcJgWDB3EG8rKM2T8eNpDi+FTBblMGvHcT/gK5kycCgYAQhAymYFUIc/HlfdoYjGnyLShO/Jj5IkID12QBmdwqdLGFbJ9T2PiTmlvO8mDt/TojR9cUn4vxoajsYJdzvEEsuQuC+Jbg9SpDBrlWKHKN/YFjLIXLAQs1oizG2ablk9XO74uqA3Y6fhtcifV+cVGRwsOs7pe3WJ24sBoGBacA8QKBgA50c3nUXn6wHPkriIFi8gkON0Ad7Ne3ik9cXTgX6BhM1p7HxaY0/V0KgIxQzhX9gIYqj5xPgHZiKQ2Ol4/8YJZ+aX/n/HTAAyt6D9owHJH+OnT5boshYUX7enWEXd/hWIIBXCtiNOYbZEZ1LboaYI9u0Ouc5ivT+mA/aKORVQsDAoGAAms7G8K4zCJnHDQQ4lFWhu1ZTId6mnmmD4iXAKfRZ2YT3ChmRR7mVlOmK1e4y71gZUBEsKsnSAcvrhqmsQczNGCyKNOxztfLWBfLctFHDFP6CsK54uwhTU3qRSyqzk5DCcravoKvWV+vQ0rzj+LKHeJX0pFDrya+FkXFvjWk7Iw=',
    //异步通知地址
    //  'notify_url' => "http://alipaytest.pinecc.cn/alipay.trade.page.pay-PHP-UTF-8/notify_url.php",
    'notify_url' => "https://mini3.pinecc.cn/pay/Notifyurl/NotifyUrl",
    //同步跳转
    // 'return_url' => "http://alipaytest.pinecc.cn/alipay.trade.page.pay-PHP-UTF-8/return_url.php",
    'return_url' => "https://mini3.pinecc.cn/pay/Returnurl/RetrunUrl",
    //编码格式
    'charset' => "UTF-8",
    //签名方式
    'sign_type'=>"RSA2",
    //支付宝网关
    'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
    //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
    'alipay_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgJDRAjWFdVhOxiSp32unaDI7zPym1vgWFQVX2xCkGiBTbJK7a465j6G1H5E31GgE8HzV65E3695Vab4EYGqHjOfSTXo/Q7LVdqgCiZjH/b/0czE9jy3qgVDAGiXKS6XrCSDsp/02b775ndD3Oa3s0IUe3zpYWwMAPa0PXS2QV+juuugxKO0iB6hOhprqJqmo4Xx46lFGPA1gdRtORbAwLJxv9vuyJlkh+gBRgDIxVyRhs5KxdkreAvblLu7DDxV9aoJ6I9wPfZExzVsIfxKgBDPPAI8XRyiQdHGKGrS9maQ7G643XBZPrs2+yLa6qMfVHVJMQz5g4XdPqLblGkwTZQIDAQAB',

];


