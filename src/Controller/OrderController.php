<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Http\Attribute\IsGranted;
use Dompdf\Dompdf;
use Dompdf\Options;



#[IsGranted('ROLE_ADMIN')]
#[Route('/order')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $order = new Order();

    $order->setOrderDate(new \DateTime());

    
    $form = $this->createForm(OrderType::class, $order);
    $form->handleRequest($request);

    // Load all products to show in the dropdown
    $products = $entityManager->getRepository(Product::class)->findAll();

    // Handle standard Symfony form submission (optional)
   if ($form->isSubmitted() &&  $form->isValid())     {


    if (!$order->getOrderDate()) {
        $order->setOrderDate(new \DateTime());
    }
    if (!$order->getCreatedAt()) {
        $order->setCreatedAt(new \DateTimeImmutable());
    }

    if (!$order->getProduct()) {
        $defaultProduct = $entityManager->getRepository(Product::class)->find(1); 
        $order->setProduct($defaultProduct);
    }


    $entityManager->persist($order);
    $entityManager->flush();

    return $this->redirectToRoute('app_order_index');
}

    // Handle custom POST from JS form
    if ($request->isMethod('POST')&& !$form->isSubmitted()) {
        $username = $request->request->get('username');
        $itemsJson = $request->request->get('items');
        $items = json_decode($itemsJson, true);
        $paymentMethod = $request->request->get('paymentMethod');

        if (!$username|| !$items) {
            $this->addFlash('error', 'Please enter customer name and select at least one product.');
            return $this->redirectToRoute('app_order_new');
        }

      
        // Check if customer exists
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy([ 'username' => $username]);

        if (!$user) {
            $user = new User();
            $user->setUser($username);
            $entityManager->persist($user);
        }

        // Create Order entity
        $order = new Order();
        $order->setUser($user);
        // Determine initial status based on user role
        if ($this->isGranted('ROLE_USER')) {
            $order->setStatus('Pending');
        } elseif ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            $order->setStatus('Processing'); // if staff/admin creates directly
        }

        $order->setOrderDate(new \DateTime());
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setPaymentMethod($paymentMethod);
        

        // TEMP: Save first product only (replace with OrderItem later)
        $firstProduct = $entityManager->getRepository(Product::class)->find($items[0]['id']);
        $order->setProduct($firstProduct);

         $product = $order->getProduct();

         

        // Compute total
        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['qty'];
        }
        $order->setTotalPrice($total);

        // Save Order
        $entityManager->persist($order);
        $entityManager->flush();

        $this->addFlash('success', 'Order created successfully!');
        return $this->redirectToRoute('app_order_index');
    }

    return $this->render('order/new.html.twig', [
        'form' => $form->createView(),
        'products' => $products,
    ]);
}


    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        $items = [
            [
                'name' => $order->getProduct()->getName(),
                'quantity' => 1, // replace with real quantity if available
                'price' => $order->getProduct()->getPrice(),
            ]
        ];

        $total = array_reduce($items, fn($sum, $item) => $sum + ($item['quantity'] * $item['price']), 0);

        return $this->render('order/show.html.twig', [
            'order' => $order,
            'items' => $items,
            'total' => $total,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($order);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }

  #[Route('/order/{id}/receipt', name: 'app_order_receipt')]
    public function receipt(Order $order): Response
    {
        // Assuming each order has a single product for now
        $items = [
            [
                'name' => $order->getProduct()->getName(),
                'quantity' => 1, // or get quantity if you have it
                'price' => $order->getProduct()->getPrice(),
            ]
        ];

        $total = array_reduce($items, fn($sum, $item) => $sum + ($item['quantity'] * $item['price']), 0);

        return $this->render('order/receipt.html.twig', [
            'order' => $order,
            'user_name' => $order->getUser()->getUsername(),
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/{id}/receipt/pdf', name: 'app_order_receipt_pdf')]
    public function receiptPdf(Order $order): Response
    {
        $items = [
            [
                'name' => $order->getProduct()->getName(),
                'quantity' => 1,
                'price' => $order->getProduct()->getPrice(),
            ]
        ];

        $total = array_reduce($items, fn($sum, $item) => $sum + ($item['quantity'] * $item['price']), 0);

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('order/receipt.html.twig', [
            'order' => $order,
            'user_name' => $order->getUser()->getUsername(),
            'items' => $items,
            'total' => $total,
            'pdf' => true, // optional flag for styling PDF
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fileName = 'receipt_' . $order->getId() . '.pdf';

        return new Response($dompdf->stream($fileName, ["Attachment" => true]));
    }



    

#[Route('/order/{id}/update-status', name: 'order_update_status', methods: ['POST'])]
public function updateStatus(Request $request, Order $order, EntityManagerInterface $em): Response
{
    if (!$this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
        throw $this->createAccessDeniedException();
    }

    $status = $request->request->get('status');
    $order->setStatus($status);
    $em->flush();

    return $this->redirectToRoute('app_order_index'); // or wherever
}




}

