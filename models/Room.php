<?php

//namespace models;

class Room
{
    public const DB_TABLE = "room";

    public ?int $room_id;
    public ?string $name;
    public ?string $no;
    public ?string $phone;

    /**
     * @param int|null $room_id
     * @param string|null $name
     * @param string|null $no
     * @param string|null $phone
     */
    public function __construct(?int $room_id = null, ?string $name = null, ?string $no = null, ?string $phone = null)
    {
        $this->room_id = $room_id;
        $this->name = $name;
        $this->no = $no;
        $this->phone = $phone;
    }

    public static function findByID(int $id): ?self
    {
        $pdo = PDOProvider::get();
        $stmt = $pdo->prepare("SELECT * FROM `" . self::DB_TABLE . "` WHERE `room_id`= :roomId");
        $stmt->execute(['roomId' => $id]);

        if ($stmt->rowCount() < 1)
            return null;

        $room = new self();
        $room->hydrate($stmt->fetch());
        return $room;
    }

    /**
     * @return Room[]
     */
    public static function getAll($sorting = []): array
    {
        $sortSQL = "";
        if (count($sorting)) {
            $SQLchunks = [];
            foreach ($sorting as $field => $direction)
                $SQLchunks[] = "`{$field}` {$direction}";

            $sortSQL = " ORDER BY " . implode(', ', $SQLchunks);
        }

        $pdo = PDOProvider::get();
        $stmt = $pdo->prepare("SELECT * FROM `" . self::DB_TABLE . "`" . $sortSQL);
        $stmt->execute([]);

        $rooms = [];
        while ($roomData = $stmt->fetch()) {
            $room = new Room();
            $room->hydrate($roomData);
            $rooms[] = $room;
        }

        return $rooms;
    }

    private function hydrate(array|object $data)
    {
        $fields = ['room_id', 'name', 'no', 'phone'];
        if (is_array($data)) {
            foreach ($fields as $field) {
                if (array_key_exists($field, $data))
                    $this->{$field} = $data[$field];
            }
        } else {
            foreach ($fields as $field) {
                if (property_exists($data, $field))
                    $this->{$field} = $data->{$field};
            }
        }
    }

    public function insert(): bool
    {
        $query = "INSERT INTO " . self::DB_TABLE . " (`name`, `no`, `phone`) VALUES (:name, :no, :phone)";
        $stmt = PDOProvider::get()->prepare($query);
        $result = $stmt->execute(['name' => $this->name, 'no' => $this->no, 'phone' => $this->phone]);
        if (!$result)
            return false;

        $this->room_id = PDOProvider::get()->lastInsertId();
        return true;
    }

    public function update(): bool
    {
        if (!isset($this->room_id) || !$this->room_id)
            throw new Exception("Cannot update model without ID");

        $query = "UPDATE " . self::DB_TABLE . " SET `name` = :name, `no` = :no, `phone` = :phone WHERE `room_id` = :roomId";
        $stmt = PDOProvider::get()->prepare($query);
        return $stmt->execute(['roomId' => $this->room_id, 'name' => $this->name, 'no' => $this->no, 'phone' => $this->phone]);
    }

    public function delete(): bool
    {
        return self::deleteByID($this->room_id);
    }

    public static function deleteByID(int $roomId): bool
    {
        $query = "DELETE FROM `" . self::DB_TABLE . "` WHERE `room_id` = :roomId";
        $stmt = PDOProvider::get()->prepare($query);
        return $stmt->execute(['roomId' => $roomId]);
    }

    public function validate(&$errors = [])
    {
        if (!isset($this->name) || (!$this->name)) {
            $errors['name'] = 'Název nesmí být prázdný.';
        } elseif (mb_strlen($this->name, "UTF-8") > 11) {
            $errors['name'] = 'Název je moc dlouhý. Max 10 znaků (písmena + číslice + povolené symboly).';
        } elseif (!preg_match('/^([\p{L}|0-9][\p{L}\-0-9_-]*)$/u', $this->name)) {
            $errors['name'] = 'Název místnosti musí obsahovat jen písmena s diakritikou a čísla 0-9 nebo "-", "_".  Písmena nebo čísla musí být na začátku.';
        }

        if (isset($this->phone)) {
            if (!preg_match('/^([0-9]{3}\s){2}[0-9]{3}$/', $this->phone)) {
                $errors['phone'] = 'Špatný format tel. čísla, musí obsahovat pouze čísla např. (987 654 432).';
            }
        }

        if (!isset($this->no) || (!$this->no)) {
            $errors['no'] = 'Číslo místnosti musí být vyplněno.';
        } elseif (!preg_match('/^([0-9]{1,10})$/', $this->no)) {
            $errors['no'] = 'Číslo místnosti musí být číslo bez obsahu písmen a znaků (s,@,/-, mezery a i mezery mezi čísli atd..) s maximálním počtem 10 čísel.';
        }
    }

    public static function readPost(): self
    {
        $room = new Room();

        $room->room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);

        $room->name = filter_input(INPUT_POST, 'name');
        if ($room->name)
            $room->name = trim($room->name);

        $room->no = filter_input(INPUT_POST, 'no');
        if ($room->no)
            $room->no = trim($room->no);

        $room->phone = filter_input(INPUT_POST, 'phone');
        if ($room->phone)
            $room->phone = trim($room->phone);

        if (!$room->phone)
            $room->phone = null;

        return $room;
    }
}
