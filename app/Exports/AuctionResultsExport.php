<?php

namespace App\Exports;

use App\Models\AuctionResult;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuctionResultsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return AuctionResult::with('security')->latest('auction_date')->get();
    }

    public function headings(): array
    {
        return [
            'Auction Number',
            'Security Name',
            'ISIN',
            'Auction Date',
            'Value Date',
            'Tenor (Days)',
            'Amount Offered',
            'Amount Subscribed',
            'Amount Sold',
            'Non-Competitive Sales',
            'Total Amount Sold',
            'Stop Rate (%)',
            'Marginal Rate (%)',
            'True Yield (%)',
            'Bid Cover Ratio',
            'Status',
        ];
    }

    public function map($auctionResult): array
    {
        return [
            $auctionResult->auction_number,
            $auctionResult->security->security_name,
            $auctionResult->security->isin,
            $auctionResult->auction_date->format('Y-m-d'),
            $auctionResult->value_date->format('Y-m-d'),
            $auctionResult->tenor_days,
            $auctionResult->amount_offered,
            $auctionResult->amount_subscribed,
            $auctionResult->amount_sold,
            $auctionResult->non_competitive_sales,
            $auctionResult->total_amount_sold,
            $auctionResult->stop_rate,
            $auctionResult->marginal_rate,
            $auctionResult->true_yield,
            $auctionResult->bid_cover_ratio,
            $auctionResult->status,
        ];
    }
}
