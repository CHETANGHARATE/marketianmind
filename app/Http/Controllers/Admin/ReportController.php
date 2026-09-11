<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportingService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportingService $reportingService
    ) {}

    /**
     * Executive Overview Summary Dashboard.
     */
    public function index(Request $request): View
    {
        $dateFilter = $this->reportingService->parseDateRange(
            $request->query('range', '30d'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        $summary = $this->reportingService->getExecutiveSummary($dateFilter['start'], $dateFilter['end']);
        $topCourses = $this->reportingService->getCoursePerformanceReport($dateFilter['start'], $dateFilter['end'])->take(5);
        $recentOrders = $this->reportingService->getSalesReport($dateFilter['start'], $dateFilter['end'])['recent_orders']->take(5);

        return view('admin.reports.index', compact('summary', 'dateFilter', 'topCourses', 'recentOrders'));
    }

    /**
     * Sales & Revenue Detailed Report.
     */
    public function sales(Request $request): View
    {
        $dateFilter = $this->reportingService->parseDateRange(
            $request->query('range', '30d'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        $salesData = $this->reportingService->getSalesReport($dateFilter['start'], $dateFilter['end']);

        return view('admin.reports.sales', [
            'dateFilter' => $dateFilter,
            'summary' => $salesData['summary'],
            'statusBreakdown' => $salesData['status_breakdown'],
            'dailyTrend' => $salesData['daily_trend'],
            'recentOrders' => $salesData['recent_orders'],
        ]);
    }

    /**
     * Course Catalog Performance Report.
     */
    public function courses(Request $request): View
    {
        $dateFilter = $this->reportingService->parseDateRange(
            $request->query('range', '30d'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        $coursesReport = $this->reportingService->getCoursePerformanceReport($dateFilter['start'], $dateFilter['end']);
        $summary = $this->reportingService->getExecutiveSummary($dateFilter['start'], $dateFilter['end']);

        return view('admin.reports.courses', compact('coursesReport', 'summary', 'dateFilter'));
    }

    /**
     * Enrollment & Academic Completion Report.
     */
    public function enrollments(Request $request): View
    {
        $dateFilter = $this->reportingService->parseDateRange(
            $request->query('range', '30d'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        $enrollmentData = $this->reportingService->getEnrollmentReport($dateFilter['start'], $dateFilter['end']);

        return view('admin.reports.enrollments', [
            'dateFilter' => $dateFilter,
            'summary' => $enrollmentData['summary'],
            'dailyTrend' => $enrollmentData['daily_trend'],
            'recentCompletions' => $enrollmentData['recent_completions'],
        ]);
    }

    /**
     * Coupon & Discount Promotion Report.
     */
    public function coupons(Request $request): View
    {
        $dateFilter = $this->reportingService->parseDateRange(
            $request->query('range', '30d'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        $couponsReport = $this->reportingService->getCouponPerformanceReport($dateFilter['start'], $dateFilter['end']);
        $summary = $this->reportingService->getExecutiveSummary($dateFilter['start'], $dateFilter['end']);

        return view('admin.reports.coupons', compact('couponsReport', 'summary', 'dateFilter'));
    }

    /**
     * Stream CSV Export for Sales & Orders.
     */
    public function exportSales(Request $request): StreamedResponse
    {
        $dateFilter = $this->reportingService->parseDateRange(
            $request->query('range', 'all'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return $this->reportingService->streamSalesCsv($dateFilter['start'], $dateFilter['end']);
    }

    /**
     * Stream CSV Export for Course Performance.
     */
    public function exportCourses(Request $request): StreamedResponse
    {
        $dateFilter = $this->reportingService->parseDateRange(
            $request->query('range', 'all'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return $this->reportingService->streamCoursesCsv($dateFilter['start'], $dateFilter['end']);
    }

    /**
     * Stream CSV Export for Student Enrollments.
     */
    public function exportEnrollments(Request $request): StreamedResponse
    {
        $dateFilter = $this->reportingService->parseDateRange(
            $request->query('range', 'all'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return $this->reportingService->streamEnrollmentsCsv($dateFilter['start'], $dateFilter['end']);
    }
}