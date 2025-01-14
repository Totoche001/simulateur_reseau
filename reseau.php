<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulation Parc Informatique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #network {
            width: 100%;
            height: 600px;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
        }
        .error-message {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Parc Informatique</a>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-header">
                        Configuration du Parc
                    </div>
                    <div class="card-body">
                        <form id="parkForm">
                            <div class="mb-3">
                                <label class="form-label">Nom du Parc Informatique</label>
                                <input type="text" class="form-control" id="parkName" required>
                                <div id="parkNameError" class="error-message"></div>
                            </div>
                            <button type="submit" class="btn btn-primary">Créer le Parc</button>
                        </form>
                    </div>
                </div>

                <div class="card" id="equipmentCard" style="display: none;">
                    <div class="card-header">
                        Ajouter un équipement
                    </div>
                    <div class="card-body">
                        <form id="equipmentForm">
                            <div class="mb-3">
                                <label class="form-label">Type d'équipement</label>
                                <select class="form-select" id="equipmentType">
                                    <option value="routeur">Routeur</option>
                                    <option value="switch">Switch</option>
                                    <option value="ordinateur">Ordinateur</option>
                                    <option value="imprimante">Imprimante</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" id="equipmentName" required>
                                <div id="nameError" class="error-message"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IP</label>
                                <input type="text" class="form-control" id="equipmentIP" required>
                                <div id="ipError" class="error-message"></div>
                            </div>
                            <button type="submit" class="btn btn-primary">Ajouter</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <span id="parkTitle">Parc informatique</span>
                        <div class="btn-group float-end">
                            <button class="btn btn-sm btn-outline-primary" onclick="network.enableEditMode()">Modifier</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="network.disableEditMode()">Terminer</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="network"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    class Equipment {
        private $id;
        private $type;
        private $name;
        private $ip;

        public function __construct($type, $name, $ip) {
            $this->id = uniqid();
            $this->type = $type;
            $this->name = $name;
            $this->ip = $ip;
        }

        public function toArray() {
            return [
                'id' => $this->id,
                'type' => $this->type,
                'name' => $this->name,
                'ip' => $this->ip
            ];
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['parkName'])) {
            if (empty($data['parkName'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Le nom du parc ne peut pas être vide']);
                exit;
            }
            file_put_contents('park_name.txt', $data['parkName']);
            echo json_encode(['success' => true]);
            exit;
        }
        
        // Validation des données
        if (empty($data['name']) || empty($data['ip']) || empty($data['type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tous les champs sont obligatoires']);
            exit;
        }

        // Validation de l'adresse IP
        if (!filter_var($data['ip'], FILTER_VALIDATE_IP)) {
            http_response_code(400);
            echo json_encode(['error' => 'Adresse IP invalide']);
            exit;
        }

        $equipment = new Equipment($data['type'], $data['name'], $data['ip']);
        
        $equipments = [];
        if (file_exists('equipments.json')) {
            $equipments = json_decode(file_get_contents('equipments.json'), true);
        }
        
        $equipments[] = $equipment->toArray();
        file_put_contents('equipments.json', json_encode($equipments));
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vis-network@9.1.2/dist/vis-network.min.js"></script>
    <script>
        const nodes = new vis.DataSet();
        const edges = new vis.DataSet();
        
        const options = {
            nodes: {
                shape: 'image',
                size: 30,
                font: {
                    size: 16
                }
            },
            edges: {
                width: 2,
                arrows: {
                    to: { enabled: true }
                },
                smooth: {
                    type: 'straightCross'
                }
            },
            manipulation: {
                enabled: false,
                addNode: true,
                editNode: true,
                deleteNode: true,
                addEdge: true,
                deleteEdge: true,
                editEdge: true
            },
            physics: {
                enabled: true,
                hierarchicalRepulsion: {
                    nodeDistance: 150
                },
                stabilization: {
                    iterations: 100
                }
            },
            layout: {
                hierarchical: {
                    enabled: true,
                    direction: 'UD',
                    sortMethod: 'directed',
                    levelSeparation: 150,
                    nodeSpacing: 150
                }
            },
            interaction: {
                dragNodes: true,
                dragView: true,
                zoomView: true,
                selectable: true,
                selectConnectedEdges: true
            }
        };

        const network = new vis.Network(
            document.getElementById('network'),
            { nodes, edges },
            options
        );

        network.enableEditMode = function() {
            this.setOptions({ manipulation: { enabled: true } });
        };

        network.disableEditMode = function() {
            this.setOptions({ manipulation: { enabled: false } });
        };

        function validateIP(ip) {
            const ipRegex = /^(\d{1,3}\.){3}\d{1,3}$/;
            if (!ipRegex.test(ip)) return false;
            const parts = ip.split('.');
            return parts.every(part => parseInt(part) >= 0 && parseInt(part) <= 255);
        }

        const getImageForType = (type) => {
            switch(type) {
                case 'routeur': return 'images/router.png';
                case 'switch': return 'images/switch.png';
                case 'ordinateur': return 'images/computer.png';
                case 'imprimante': return 'images/printer.png';
                default: return '';
            }
        };

        document.getElementById('parkForm').addEventListener('submit', (e) => {
            e.preventDefault();
            
            const parkName = document.getElementById('parkName').value;
            const errorDiv = document.getElementById('parkNameError');
            
            if (!parkName.trim()) {
                errorDiv.textContent = "Le nom du parc ne peut pas être vide";
                errorDiv.style.display = 'block';
                return;
            }

            document.getElementById('parkTitle').textContent = parkName;
            document.getElementById('parkForm').style.display = 'none';
            document.getElementById('equipmentCard').style.display = 'block';
            errorDiv.style.display = 'none';
        });

        document.getElementById('equipmentForm').addEventListener('submit', (e) => {
            e.preventDefault();
            
            const type = document.getElementById('equipmentType').value;
            const name = document.getElementById('equipmentName').value;
            const ip = document.getElementById('equipmentIP').value;
            
            const nameError = document.getElementById('nameError');
            const ipError = document.getElementById('ipError');
            
            nameError.style.display = 'none';
            ipError.style.display = 'none';

            if (!name.trim()) {
                nameError.textContent = "Le nom ne peut pas être vide";
                nameError.style.display = 'block';
                return;
            }

            if (!validateIP(ip)) {
                ipError.textContent = "Adresse IP invalide";
                ipError.style.display = 'block';
                return;
            }

            const nodeId = Date.now();
            const node = {
                id: nodeId,
                label: `${name}\n${ip}`,
                image: getImageForType(type),
                type: type,
                subnet: ip.split('.').slice(0, 3).join('.')
            };
            
            nodes.add(node);

            if (type === 'switch') {
                const routerNodes = nodes.get({
                    filter: function(node) {
                        return node.type === 'routeur' && node.subnet === ip.split('.').slice(0, 3).join('.');
                    }
                });
                
                if (routerNodes.length > 0) {
                    edges.add({
                        from: routerNodes[0].id,
                        to: nodeId
                    });
                }
            } else if (type === 'ordinateur' || type === 'imprimante') {
                const switchNodes = nodes.get({
                    filter: function(node) {
                        return node.type === 'switch' && node.subnet === ip.split('.').slice(0, 3).join('.');
                    }
                });
                
                if (switchNodes.length > 0) {
                    edges.add({
                        from: switchNodes[0].id,
                        to: nodeId
                    });
                }
            }

            document.getElementById('equipmentForm').reset();
        });

        network.on("click", function(params) {
            if (params.nodes.length > 0) {
                const nodeId = params.nodes[0];
                const node = nodes.get(nodeId);
                const [oldName, oldIP] = node.label.split('\n');
                const newName = prompt("Nouveau nom:", oldName);
                const newIP = prompt("Nouvelle IP:", oldIP);

                if (newName && newIP) {
                    if (!validateIP(newIP)) {
                        alert("Adresse IP invalide");
                        return;
                    }
                    nodes.update({ 
                        id: nodeId, 
                        label: `${newName}\n${newIP}`,
                        subnet: newIP.split('.').slice(0, 3).join('.')
                    });
                }
            }
        });
    </script>
</body>
</html>
