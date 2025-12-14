<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Order;
use App\Entity\Customer;
use App\Entity\Category;
use App\Form\ProductType;
use App\Repository\LoyaltyRepository;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use App\Repository\CategoryRepository;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Http\Attribute\IsGranted;


#[Route('/dashboard', name: 'app_dashboard')]
final class DashboardController extends AbstractController
{
    public function __invoke(
        Request $request,
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        CategoryRepository $categoryRepository,
        CustomerRepository $customerRepository,
        loyaltyRepository $loyaltyRepository,
        EntityManagerInterface $em
    ): Response {
        // Fetch all data
        $products = $productRepository->findAll();
        $orders = $orderRepository->findAll();
        $customers = $customerRepository->findAll();
        $categories = $categoryRepository->findAll();

        $loyaltyUsers = $loyaltyRepository->findAll(); // gets all loyalty users


        // Sales today
        $today = new \DateTimeImmutable('today');
        $salesToday = array_reduce($orders, fn($sum, Order $o) => $o->getOrderDate() >= $today ? $sum + $o->getTotalPrice() : $sum, 0);

        // Pending & completed orders
        $pendingOrders = array_filter($orders, fn(Order $o) => $o->getStatus() === 'pending');
        $completedOrders = array_filter($orders, fn(Order $o) => $o->getStatus() === 'completed');

        // Daily earnings for last 7 days
        $earningsByDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = (new \DateTimeImmutable())->modify("-$i days")->format('Y-m-d');
            $earningsByDay[$day] = 0;
        }
        foreach ($orders as $order) {
            $day = $order->getOrderDate()->format('Y-m-d');
            if (isset($earningsByDay[$day])) {
                $earningsByDay[$day] += $order->getTotalPrice();
            }
        }

        // Most sold products
        $productSales = [];

        foreach ($orders as $order) {
            $product = $order->getProduct(); // get the related Product
            if (!$product) continue;

            $productId = $product->getId();
            $productSales[$productId]['product'] = $product->getName();
            $productSales[$productId]['qty'] = ($productSales[$productId]['qty'] ?? 0) + 1;
        }

        // Sort by quantity sold
        $quantities = array_column($productSales, 'qty');
        arsort($quantities);

        // Build topProducts array
        $topProducts = [];
        foreach ($quantities as $productId => $qty) {
            if (isset($productSales[$productId])) {
                $topProducts[$productId] = $productSales[$productId];
            }
        }

        // Take top 5 products
        $topProducts = array_slice($topProducts, 0, 5, true);

                

        // Top categories
        $categorySales = [];
        foreach ($orders as $order) {
            $catName = $order->getProduct()->getCategory()->getName();
            $categorySales[$catName] = ($categorySales[$catName] ?? 0) + 1; // each order = 1 product
        }
        arsort($categorySales);
        $topCategories = array_slice($categorySales, 0, 5, true);


        // Daily customers
        $customersByDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = (new \DateTimeImmutable())->modify("-$i days")->format('Y-m-d');
            $customersByDay[$day] = 0;
        }
        foreach ($customers as $customer) {
            $day = $customer->getCreatedAt()->format('Y-m-d');
            if (isset($customersByDay[$day])) $customersByDay[$day]++;
        }

        // Monthly revenue
        $revenueByMonth = [];
        for ($i = 0; $i < 12; $i++) {
            $month = (new \DateTimeImmutable("first day of -$i month"))->format('Y-m');
            $revenueByMonth[$month] = 0;
        }
        foreach ($orders as $order) {
            $month = $order->getOrderDate()->format('Y-m');
            if (isset($revenueByMonth[$month])) $revenueByMonth[$month] += $order->getTotalPrice();
        }

        return $this->render('dashboard/index.html.twig', [
            'products' => $products,
            'orders' => $orders,
            'customers' => $customers,
            'categories' => $categories,
            'salesToday' => $salesToday,
            'pendingOrders' => count($pendingOrders),
            'completedOrders' => count($completedOrders),
            'earnings' => $earningsByDay,
            'topProducts' => $topProducts,
            'topCategories' => $topCategories,
            'customersByDay' => $customersByDay,
            'revenueByMonth' => $revenueByMonth,
            'loyaltyUsers' => $loyaltyUsers,
        ]);
    }
}




