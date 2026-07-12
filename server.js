const express = require('express');
const app = express();
app.use(express.json()); // To parse JSON bodies

// Mock Database (Your FYP "Data Store")
let requests = [
    { id: 'REQ-442', citizen: 'USR-8821', material: 'Plastic', weight: 5.2, status: 'Pending' },
    { id: 'REQ-441', citizen: 'USR-9012', material: 'Metal', weight: 1.8, status: 'Pending' }
];

// --- CRUD FOR MANAGE REQUESTS ---

// 1. READ: Get all pending requests
app.get('/api/requests', (req, res) => {
    res.json(requests);
});

// 2. UPDATE: Verify (Approve) a request
app.patch('/api/requests/:id', (req, res) => {
    const { id } = req.params;
    const { status } = req.body;
    let request = requests.find(r => r.id === id);
    if (request) {
        request.status = status;
        res.status(200).json({ message: `Request ${id} updated to ${status}` });
    } else {
        res.status(404).json({ message: "Request not found" });
    }
});

// 3. DELETE: Remove a rejected request
app.delete('/api/requests/:id', (req, res) => {
    requests = requests.filter(r => r.id !== req.params.id);
    res.json({ message: "Request deleted" });
});

// --- CRUD FOR INVENTORY ---
let inventory = [];

// 4. CREATE: Add to Inventory (This happens when a request is verified)
app.post('/api/inventory', (req, res) => {
    const newEntry = req.body; // { material, weight, date }
    inventory.push(newEntry);
    res.status(201).json(newEntry);
});

app.listen(3000, () => console.log('FYP Backend running on port 3000'));