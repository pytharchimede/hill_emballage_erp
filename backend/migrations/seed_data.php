<?php

/**
 * Insertion de données de test pour HILL EMBALLAGE
 */

require_once '../config/database.php';

class SeedData
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function runAll()
    {
        echo "🌱 Insertion des données de test...\n\n";

        $this->seedUsers();
        $this->seedDepots();
        $this->seedProducts();
        $this->seedClients();
        $this->seedStock();
        $this->seedFidelityRewards();

        echo "\n✅ Toutes les données de test ont été insérées!\n";
    }

    private function seedUsers()
    {
        $users = [
            ['admin', 'admin@hillemballage.com', password_hash('admin123', PASSWORD_DEFAULT), 'Administrateur Principal', 'admin', '22012345'],
            ['comptable1', 'comptable@hillemballage.com', password_hash('compta123', PASSWORD_DEFAULT), 'Marie Kouassi', 'comptable', '22234567'],
            ['livreur1', 'livreur1@hillemballage.com', password_hash('livreur123', PASSWORD_DEFAULT), 'Koffi Yao', 'livreur', '22345678'],
            ['vendeur1', 'vendeur1@hillemballage.com', password_hash('vendeur123', PASSWORD_DEFAULT), 'Aya Traoré', 'vendeur', '22456789']
        ];

        $sql = "INSERT INTO users (username, email, password, full_name, role, phone) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        foreach ($users as $user) {
            $stmt->execute($user);
        }

        echo "✅ Utilisateurs créés\n";
    }

    private function seedDepots()
    {
        $depots = [
            ['Entrepôt Principal', 'ENT001', 'Zone Industrielle, Abidjan', 'Abidjan', 'principal', 1],
            ['Dépôt Cocody', 'DEP001', 'Cocody, Abidjan', 'Cocody', 'sous_depot', 3],
            ['Dépôt Yopougon', 'DEP002', 'Yopougon, Abidjan', 'Yopougon', 'sous_depot', 4],
            ['Dépôt Bouaké', 'DEP003', 'Centre-ville, Bouaké', 'Bouaké', 'sous_depot', 4]
        ];

        $sql = "INSERT INTO depots (nom, code_depot, adresse, zone, type_depot, responsable_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        foreach ($depots as $depot) {
            $stmt->execute($depot);
        }

        echo "✅ Dépôts créés\n";
    }

    private function seedProducts()
    {
        $products = [
            ['Sac Plastique 25kg', 'SAC25', 'Sac plastique résistant 25kg', 'pièce', 150.00, 175.00, 2],
            ['Sac Plastique 50kg', 'SAC50', 'Sac plastique résistant 50kg', 'pièce', 250.00, 290.00, 3],
            ['Sac Jute 25kg', 'JUTE25', 'Sac en jute naturel 25kg', 'pièce', 300.00, 350.00, 4],
            ['Sac Jute 50kg', 'JUTE50', 'Sac en jute naturel 50kg', 'pièce', 450.00, 520.00, 5],
            ['Carton Standard', 'CART001', 'Carton d\'emballage standard', 'pièce', 500.00, 580.00, 3],
            ['Film Plastique', 'FILM001', 'Film plastique transparent', 'rouleau', 2500.00, 2900.00, 10],
            ['Sangles Plastique', 'SANG001', 'Sangles de cerclage plastique', 'mètre', 50.00, 60.00, 1]
        ];

        $sql = "INSERT INTO products (nom, code_produit, description, unite, prix_unitaire, prix_credit, points_fidelite) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        foreach ($products as $product) {
            $stmt->execute($product);
        }

        echo "✅ Produits créés\n";
    }

    private function seedClients()
    {
        $clients = [
            ['CLI001', 'Kouamé', 'Jean-Baptiste', '22501234', 'kouame@email.com', 'Cocody, Abidjan', 'Cocody', 'particulier', 500000.00, 0.00, 150],
            ['CLI002', 'SARL EMBALLAGE', '', '22601234', 'contact@sarlemballage.ci', 'Zone 4, Abidjan', 'Marcory', 'entreprise', 2000000.00, 0.00, 500],
            ['CLI003', 'Traoré', 'Fatou', '22701234', 'traore.fatou@gmail.com', 'Yopougon, Abidjan', 'Yopougon', 'particulier', 300000.00, 0.00, 75],
            ['CLI004', 'AGRI-EXPORT CI', '', '22801234', 'info@agriexport.ci', 'Bouaké Centre', 'Bouaké', 'entreprise', 5000000.00, 0.00, 1200]
        ];

        $sql = "INSERT INTO clients (code_client, nom, prenoms, telephone, email, adresse, zone, type_client, credit_limite, solde_credit, points_fidelite, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);

        foreach ($clients as $client) {
            $stmt->execute($client);
        }

        echo "✅ Clients créés\n";
    }

    private function seedStock()
    {
        // Stock entrepôt principal
        $stockData = [
            [1, 1, 1000, 0, 50], // Sac 25kg
            [1, 2, 800, 0, 30],  // Sac 50kg
            [1, 3, 500, 0, 25],  // Jute 25kg
            [1, 4, 300, 0, 15],  // Jute 50kg
            [1, 5, 2000, 0, 100], // Cartons
            [1, 6, 50, 0, 5],    // Film plastique
            [1, 7, 10000, 0, 500], // Sangles
            // Stock dépôt Cocody
            [2, 1, 200, 0, 20],
            [2, 2, 150, 0, 15],
            [2, 5, 300, 0, 30],
            // Stock dépôt Yopougon
            [3, 1, 180, 0, 20],
            [3, 3, 100, 0, 10],
            [3, 6, 10, 0, 2]
        ];

        $sql = "INSERT INTO stock (depot_id, product_id, quantite_disponible, quantite_reservee, seuil_alerte) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        foreach ($stockData as $stock) {
            $stmt->execute($stock);
        }

        echo "✅ Stock initial créé\n";
    }

    private function seedFidelityRewards()
    {
        $rewards = [
            ['Remise 5%', 100, 5.00, 0.00],
            ['Remise 10%', 250, 10.00, 0.00],
            ['Réduction 5000F', 500, 0.00, 5000.00],
            ['Remise 15%', 1000, 15.00, 0.00],
            ['Réduction 15000F', 1500, 0.00, 15000.00]
        ];

        $sql = "INSERT INTO fidelity_rewards (nom, points_requis, reduction_pourcentage, reduction_montant) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        foreach ($rewards as $reward) {
            $stmt->execute($reward);
        }

        echo "✅ Récompenses fidélité créées\n";
    }
}

// Exécution du seeding
if (php_sapi_name() == 'cli' || isset($_GET['seed'])) {
    $seeder = new SeedData();
    $seeder->runAll();
} else {
    echo "Pour insérer les données de test, ajoutez ?seed=1 à l'URL ou exécutez en ligne de commande.";
}
