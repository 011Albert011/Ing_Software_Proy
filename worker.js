// Aqui se simula concurrencia para validar stock y procesar compras
self.onmessage = function(e) {
    const { action, data } = e.data;
    
    if (action === 'validateStock') {
        // simula validacion de stock (recibe stock actual de BD)
        const { productId, productName, productPrice, requestedQuantity, availableStock } = data;
        const isValid = requestedQuantity <= availableStock;
        
        self.postMessage({
            action: 'stockValidated',
            productId,
            productName,
            productPrice,
            isValid,
            newStock: isValid ? availableStock - requestedQuantity : availableStock
        });
    } else if (action === 'processPayment') {
        // simula procesamiento concurrente de pago
        const { cart, total } = data;
        const success = total > 0 && cart.length > 0;
        
        self.postMessage({
            action: 'paymentProcessed',
            success,
            message: success ? 'Pago procesado exitosamente' : 'Error en pago'
        });
    }
};