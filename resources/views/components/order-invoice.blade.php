<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
</head>

<body style="font-family: Nunito, sans-serif; font-size: 15px; font-weight: 400; color: #161c2d;">

    <!-- Hero Start -->
    <div style="margin-top: 50px;">
        <table cellpadding="0" cellspacing="0"
            style="font-family: Nunito, sans-serif; font-size: 15px; font-weight: 400; max-width: 600px; border: none; margin: 0 auto; border-radius: 6px; overflow: hidden; background-color: #fff; box-shadow: 0 0 3px rgba(60, 72, 88, 0.15);">
            <thead>
                <tr
                    style="background-color: #980f08; padding: 3px 0; line-height: 68px; text-align: center; color: #fff; font-size: 24px; letter-spacing: 1px;">
                    <th scope="col">ORDER INVOICE</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td style="padding: 24px 24px 0;">
                        <table cellpadding="0" cellspacing="0" style="border: none;">
                            <tbody>
                                <tr>
                                    <td style="min-width: 130px; padding-bottom: 8px;">Invoice No. :</td>
                                    <td style="color: #8492a6;">
                                        {{ Str::substr(strtoupper($order->transaction->orderInvoice->invoice_no), 0, 10) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="min-width: 130px; padding-bottom: 8px;">Name :</td>
                                    <td style="color: #8492a6;">{{ $order->transaction->orderInvoice->customer_name }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="min-width: 130px; padding-bottom: 8px;">Type :</td>
                                    <td style="color: #8492a6;">{{ $order->transaction->orderInvoice->type }}</td>
                                </tr>
                                <tr>
                                    <td style="min-width: 130px; padding-bottom: 8px;">Date and Time:</td>
                                    <td style="color: #8492a6;">{{ now() }}</td>
                                </tr>

                                <tr>
                                    <td style="min-width: 130px; padding-bottom: 8px;">Payment Method:</td>
                                    <td style="color: #8492a6;"><strong>{{ $paymentMethod }}</strong></td>
                                </tr>

                                <tr>
                                    <td style="min-width: 130px; padding-bottom: 8px;">Payment Status:</td>
                                    <td style="color: #8492a6;"><strong>{{ $transactionStatus }}</strong></td>
                                </tr>

                                <tr>
                                    <td style="min-width: 130px; padding-bottom: 8px;">Order Status:</td>
                                    <td style="color: #8492a6;"><strong>{{ $orderStatus }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 24px;">
                        <div
                            style="display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 6px; box-shadow: 0 0 3px rgba(60, 72, 88, 0.15);">
                            <table cellpadding="0" cellspacing="0">
                                <thead class="bg-gray">
                                    <tr>
                                        <th scope="col"
                                            style="text-align: left; vertical-align: bottom; border-top: 1px solid #dee2e6; padding: 12px;">
                                            No.</th>
                                        <th scope="col"
                                            style="text-align: left; vertical-align: bottom; border-top: 1px solid #dee2e6; padding: 12px; width: 200px;">
                                            Item</th>

                                        <th scope="col"
                                            style="text-align: left; vertical-align: bottom; border-top: 1px solid #dee2e6; padding: 12px;">
                                            Qty.</th>

                                        <th scope="col"
                                            style="text-align: left; vertical-align: bottom; border-top: 1px solid #dee2e6; padding: 12px;">
                                            Unit price</th>

                                        <th scope="col"
                                            style="text-align: end; vertical-align: bottom; border-top: 1px solid #dee2e6; padding: 12px;">
                                            Total</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @if ($orderedItems->count() < 1)
                                        <tr>
                                            <th scope="row"
                                                style="text-align: left; padding: 12px; border-top: 1px solid #dee2e6;">
                                                1
                                            </th>
                                            <td style="text-align: left; padding: 12px; border-top: 1px solid #dee2e6;">
                                                {{ $order->description }} </td>
                                            <td style="text-align: left; padding: 12px; border-top: 1px solid #dee2e6;">
                                                1</td>
                                            <td style="text-align: left; padding: 12px; border-top: 1px solid #dee2e6;">
                                                {{ $order->total_amount }} </td>
                                            <td style="text-align: end; padding: 12px; border-top: 1px solid #dee2e6;">
                                                N{{ $order->total_amount }}</td>
                                        </tr>
                                    @else
                                        @foreach ($orderedItems as $orderItem)
                                            <tr>
                                                <th scope="row"
                                                    style="text-align: left; padding: 12px; border-top: 1px solid #dee2e6;">
                                                    {{ ++$loop->index }}
                                                </th>
                                                <td
                                                    style="text-align: left; padding: 12px; border-top: 1px solid #dee2e6;">
                                                    {{ $orderItem->item->item_name }} </td>
                                                <td
                                                    style="text-align: left; padding: 12px; border-top: 1px solid #dee2e6;">
                                                    {{ $orderItem->quantity }} </td>
                                                <td
                                                    style="text-align: left; padding: 12px; border-top: 1px solid #dee2e6;">
                                                    {{ $orderItem->item->item_price }} </td>
                                                <td
                                                    style="text-align: end; padding: 12px; border-top: 1px solid #dee2e6;">
                                                    N{{ $orderItem->quantity * $orderItem->item->item_price }}</td>
                                            </tr>
                                        @endforeach
                                    @endif

                                    @if ($errand)
                                        <tr style="overflow-x: hidden;">
                                            <td colspan="4" scope="row"
                                                style="text-align: left; padding: 12px; border-top: 1px solid #dee2e6;">
                                                Delivery Fee
                                            </td>
                                            <td colspan="1"
                                                style="text-align: end; padding: 12px; border-top: 1px solid #dee2e6;">
                                                N{{ $errand->delivery_fee }}</td>
                                        </tr>
                                    @endif

                                    <tr
                                        style="background-color: rgba(77, 69, 230, 0.05); color: #980f08; overflow-x: hidden;">
                                        <th scope="row"
                                            style="text-align: left; padding: 12px; border-top: 1px solid rgba(77, 69, 230, 0.05);">
                                            Total</th>
                                            
                                        @if ($errand)
                                            <td colspan="4"
                                                style="text-align: end; font-weight: 700; font-size: 18px; padding: 12px; border-top: 1px solid rgba(77, 69, 230, 0.05);">
                                                N{{ $order->total_amount + $errand->delivery_fee }}</td>
                                        @else
                                            <td colspan="4"
                                                style="text-align: end; font-weight: 700; font-size: 18px; padding: 12px; border-top: 1px solid rgba(77, 69, 230, 0.05);">
                                                N{{ $order->total_amount }}</td>
                                        @endif
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 16px 8px; color: #8492a6; background-color: #f8f9fc; text-align: center;">
                        ©
                        <script>
                            document.write(new Date().getFullYear())
                        </script> EasyBuy4Me.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Hero End -->
</body>

</html>
