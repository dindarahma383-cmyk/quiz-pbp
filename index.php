<?php

// class & relasi antar tabel (materi 1-2)
class TabelSekolah
{
    public static array $tabelJurusan = [
        'RPL' => ['kode' => 'RPL', 'nama_jurusan' => 'Rekayasa Perangkat Lunak'],
        'TKJ' => ['kode' => 'TKJ', 'nama_jurusan' => 'Teknik Komputer Jaringan'],
    ];

    public static array $tabelSiswa = [];
    public static array $tabelNilai = [];

    public static function simpanSiswa(string $nis, string $nama, string $kodeJurusan): void
    {
        if (!isset(self::$tabelJurusan[$kodeJurusan])) {
            throw new InvalidArgumentException("kode_jurusan '$kodeJurusan' tidak ada di tabelJurusan");
        }

        self::$tabelSiswa[$nis] = [
            'nis'          => $nis,
            'nama'         => $nama,
            'kode_jurusan' => $kodeJurusan,
        ];
    }

    public static function simpanNilai(string $nis, string $mapel, int $nilai): void
    {
        if (!isset(self::$tabelSiswa[$nis])) {
            throw new InvalidArgumentException("nis '$nis' belum ada di tabelSiswa");
        }

        self::$tabelNilai[] = [
            'nis'   => $nis,
            'mapel' => $mapel,
            'nilai' => $nilai,
        ];
    }

    public static function ambilNilaiByNis(string $nis): array
    {
        $rekapan = [];
        foreach (self::$tabelNilai as $nomor => $baris) {
            if ($baris['nis'] === $nis) {
                $baris['_idx'] = $nomor;
                $rekapan[] = $baris;
            }
        }
        return $rekapan;
    }

    public static function renderTabelRelasi(): void
    {
        echo "<h3>Relasi Tabel (jurusan.kode -> siswa.kode_jurusan -> nilai_mapel.nis)</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>NIS</th><th>Nama</th><th>Jurusan (FK->PK)</th><th>Nilai</th></tr>";

        foreach (self::$tabelSiswa as $nis => $siswaRow) {
            $kode = $siswaRow['kode_jurusan'];
            $namaJurusan = self::$tabelJurusan[$kode]['nama_jurusan'] ?? '-';
            $nilaiSiswa = self::ambilNilaiByNis($nis);

            if (count($nilaiSiswa) === 0) {
                $tampilNilai = "<i>belum ada</i>";
            } else {
                $satuan = [];
                foreach ($nilaiSiswa as $nItem) {
                    $satuan[] = htmlspecialchars($nItem['mapel']) . ": " . (int)$nItem['nilai'];
                }
                $tampilNilai = implode(", ", $satuan);
            }

            echo "<tr>";
            echo "<td>" . htmlspecialchars($nis) . "</td>";
            echo "<td>" . htmlspecialchars($siswaRow['nama']) . "</td>";
            echo "<td>" . htmlspecialchars("$kode - $namaJurusan") . "</td>";
            echo "<td>" . $tampilNilai . "</td>";
            echo "</tr>";
        }

        echo "</table><br>";
    }
}

// construct, destruct, modifier & constant (materi 3-4)
class AnggotaSekolah
{
    public const TAHUN_AJARAN = '2026/2027';

    protected string $nis;
    protected string $kodeJurusan;
    private string $nama;

    public function __construct(string $nis, string $nama, string $kodeJurusan)
    {
        $this->nis = $nis;
        $this->setNama($nama);
        $this->kodeJurusan = $kodeJurusan;
        TabelSekolah::simpanSiswa($nis, $this->nama, $kodeJurusan);
    }

    public function getNama(): string
    {
        return $this->nama;
    }

    public function setNama(string $nama): void
    {
        $bersih = trim($nama);
        if ($bersih === '') {
            throw new InvalidArgumentException("nama tidak boleh kosong");
        }
        $this->nama = $bersih;
    }

    protected function getNis(): string
    {
        return $this->nis;
    }

    public function detailDasar(): array
    {
        $namaJurusan = TabelSekolah::$tabelJurusan[$this->kodeJurusan]['nama_jurusan'] ?? '-';
        return [
            'nis'          => $this->getNis(),
            'nama'         => $this->getNama(),
            'jurusan'      => $this->kodeJurusan . " - " . $namaJurusan,
            'tahun_ajaran' => self::TAHUN_AJARAN,
        ];
    }

    public function __destruct()
    {
        echo "<small>destruct: " . htmlspecialchars($this->nama) . "</small><br>";
    }
}

// pewarisan & override (materi 5)
class SiswaReguler extends AnggotaSekolah
{
    private string $kelas;

    public function __construct(string $nis, string $nama, string $kodeJurusan, string $kelas)
    {
        parent::__construct($nis, $nama, $kodeJurusan);
        $this->kelas = $kelas;
    }

    public function tambahNilai(string $mapel, int $nilai): void
    {
        TabelSekolah::simpanNilai($this->nis, $mapel, $nilai);
    }

    public function detailDasar(): array
    {
        $hasil = parent::detailDasar();
        $hasil['status'] = 'Siswa Reguler';
        $hasil['info_tambahan'] = 'Kelas ' . $this->kelas;
        return $hasil;
    }
}

class SiswaPindahan extends AnggotaSekolah
{
    private string $asalSekolah;

    public function __construct(string $nis, string $nama, string $kodeJurusan, string $asalSekolah)
    {
        parent::__construct($nis, $nama, $kodeJurusan);
        $this->asalSekolah = $asalSekolah;
    }

    public function detailDasar(): array
    {
        $hasil = parent::detailDasar();
        $hasil['status'] = 'Siswa Pindahan';
        $hasil['info_tambahan'] = 'Asal: ' . $this->asalSekolah;
        return $hasil;
    }
}

// simpan crud ke file json tanpa session
$fileCrud = __DIR__ . '/data_crud.json';
$dataBawaan = [
    'siswa' => [
        ['tipe' => 'reguler',  'nis' => '202601', 'nama' => 'Alya',  'kode_jurusan' => 'RPL', 'extra' => 'XI RPL 1'],
        ['tipe' => 'pindahan', 'nis' => '202602', 'nama' => 'Bagas', 'kode_jurusan' => 'TKJ', 'extra' => 'SMK Negeri 1'],
    ],
    'nilai' => [
        ['nis' => '202601', 'mapel' => 'PBO',        'nilai' => 88],
        ['nis' => '202601', 'mapel' => 'Basis Data', 'nilai' => 85],
    ],
];

if (!file_exists($fileCrud)) {
    file_put_contents($fileCrud, json_encode($dataBawaan, JSON_PRETTY_PRINT));
}
$data = json_decode((string)file_get_contents($fileCrud), true);
if (!is_array($data) || !isset($data['siswa'], $data['nilai'])) {
    $data = $dataBawaan;
}

$pesanErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    try {
        if ($aksi === 'tambah_siswa') {
            $nisBaru = trim($_POST['nis'] ?? '');
            foreach ($data['siswa'] as $ada) {
                if ($ada['nis'] === $nisBaru) {
                    throw new InvalidArgumentException("NIS '$nisBaru' sudah ada");
                }
            }
            $data['siswa'][] = [
                'tipe'         => $_POST['tipe'] ?? 'reguler',
                'nis'          => $nisBaru,
                'nama'         => trim($_POST['nama'] ?? ''),
                'kode_jurusan' => trim($_POST['kode_jurusan'] ?? ''),
                'extra'        => trim($_POST['extra'] ?? ''),
            ];
        } elseif ($aksi === 'edit_siswa') {
            $lama = trim($_POST['nis_lama'] ?? '');
            $baru = trim($_POST['nis_baru'] ?? '');
            foreach ($data['siswa'] as $idx => $r) {
                if ($r['nis'] === $lama) {
                    $data['siswa'][$idx]['nis']          = $baru;
                    $data['siswa'][$idx]['nama']         = trim($_POST['nama'] ?? '');
                    $data['siswa'][$idx]['kode_jurusan'] = trim($_POST['kode_jurusan'] ?? '');
                    $data['siswa'][$idx]['extra']        = trim($_POST['extra'] ?? '');
                    break;
                }
            }
            if ($lama !== $baru) {
                foreach ($data['nilai'] as $idxN => $rn) {
                    if ($rn['nis'] === $lama) {
                        $data['nilai'][$idxN]['nis'] = $baru;
                    }
                }
            }
        } elseif ($aksi === 'hapus_siswa') {
            $nisDel = $_POST['nis'] ?? '';
            $tmpSiswa = [];
            foreach ($data['siswa'] as $d) {
                if ($d['nis'] !== $nisDel) {
                    $tmpSiswa[] = $d;
                }
            }
            $data['siswa'] = $tmpSiswa;

            $tmpNilai = [];
            foreach ($data['nilai'] as $d) {
                if ($d['nis'] !== $nisDel) {
                    $tmpNilai[] = $d;
                }
            }
            $data['nilai'] = $tmpNilai;
        } elseif ($aksi === 'tambah_nilai') {
            $data['nilai'][] = [
                'nis'   => trim($_POST['nis'] ?? ''),
                'mapel' => trim($_POST['mapel'] ?? ''),
                'nilai' => (int)($_POST['nilai'] ?? 0),
            ];
        } elseif ($aksi === 'hapus_nilai') {
            $target = (int)($_POST['idx'] ?? -1);
            $sisaN = [];
            foreach ($data['nilai'] as $k => $v) {
                if ($k !== $target) {
                    $sisaN[] = $v;
                }
            }
            $data['nilai'] = $sisaN;
        } elseif ($aksi === 'reset_data') {
            $data = $dataBawaan;
        }

        file_put_contents($fileCrud, json_encode($data, JSON_PRETTY_PRINT));
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (Throwable $e) {
        $pesanErr = $e->getMessage();
    }
}

$kumpulanObjek = [];
$daftarProfil = [];
foreach ($data['siswa'] as $itemS) {
    if ($itemS['tipe'] === 'pindahan') {
        $obj = new SiswaPindahan($itemS['nis'], $itemS['nama'], $itemS['kode_jurusan'], $itemS['extra']);
    } else {
        $obj = new SiswaReguler($itemS['nis'], $itemS['nama'], $itemS['kode_jurusan'], $itemS['extra']);
        foreach ($data['nilai'] as $itemN) {
            if ($itemN['nis'] === $itemS['nis']) {
                $obj->tambahNilai($itemN['mapel'], (int)$itemN['nilai']);
            }
        }
    }
    $kumpulanObjek[] = $obj;
    $daftarProfil[] = $obj->detailDasar();
}

$idEdit = $_GET['edit'] ?? '';
$rowEdit = null;
if ($idEdit !== '') {
    foreach ($data['siswa'] as $s) {
        if ($s['nis'] === $idEdit) {
            $rowEdit = $s;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quiz PBO</title>
</head>
<body>

<h2>Quiz PBO - OOP &amp; CRUD</h2>

<?php if ($pesanErr !== ''): ?>
    <p style="color:red;"><?= htmlspecialchars($pesanErr) ?></p>
<?php endif; ?>

<h3><?= $rowEdit ? 'Edit Siswa' : 'Tambah Siswa' ?></h3>
<form method="post">
    <?php if ($rowEdit): ?>
        <input type="hidden" name="aksi" value="edit_siswa">
        <input type="hidden" name="nis_lama" value="<?= htmlspecialchars($rowEdit['nis']) ?>">
        <input type="text" name="nis_baru" value="<?= htmlspecialchars($rowEdit['nis']) ?>" required>
        <input type="text" name="nama" value="<?= htmlspecialchars($rowEdit['nama']) ?>" required>
        <select name="kode_jurusan">
            <?php foreach (TabelSekolah::$tabelJurusan as $k => $j): ?>
                <option value="<?= $k ?>" <?= $rowEdit['kode_jurusan'] === $k ? 'selected' : '' ?>><?= $k ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="extra" value="<?= htmlspecialchars($rowEdit['extra']) ?>" required>
        <button type="submit">Update</button>
        <a href="index.php">Batal</a>
    <?php else: ?>
        <input type="hidden" name="aksi" value="tambah_siswa">
        <input type="text" name="nis" placeholder="NIS" required>
        <input type="text" name="nama" placeholder="Nama" required>
        <select name="kode_jurusan">
            <?php foreach (TabelSekolah::$tabelJurusan as $k => $j): ?>
                <option value="<?= $k ?>"><?= $k ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tipe">
            <option value="reguler">Reguler</option>
            <option value="pindahan">Pindahan</option>
        </select>
        <input type="text" name="extra" placeholder="Kelas / Asal Sekolah" required>
        <button type="submit">Simpan</button>
    <?php endif; ?>
</form>

<form method="post" style="display:inline;">
    <input type="hidden" name="aksi" value="reset_data">
    <button type="submit">Reset</button>
</form>

<h3>Tambah Nilai</h3>
<form method="post">
    <input type="hidden" name="aksi" value="tambah_nilai">
    <select name="nis">
        <?php foreach ($data['siswa'] as $s): ?>
            <option value="<?= htmlspecialchars($s['nis']) ?>"><?= htmlspecialchars($s['nis'] . " - " . $s['nama']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" name="mapel" placeholder="Mapel" required>
    <input type="number" name="nilai" placeholder="Nilai" min="0" max="100" required>
    <button type="submit">Simpan Nilai</button>
</form>

<h3>Data Siswa</h3>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>NIS</th>
        <th>Nama</th>
        <th>Status</th>
        <th>Jurusan</th>
        <th>Keterangan</th>
        <th>TA</th>
        <th>Aksi</th>
    </tr>
    <?php foreach ($daftarProfil as $p): ?>
    <tr>
        <td><?= htmlspecialchars($p['nis']) ?></td>
        <td><?= htmlspecialchars($p['nama']) ?></td>
        <td><?= htmlspecialchars($p['status']) ?></td>
        <td><?= htmlspecialchars($p['jurusan']) ?></td>
        <td><?= htmlspecialchars($p['info_tambahan']) ?></td>
        <td><?= htmlspecialchars($p['tahun_ajaran']) ?></td>
        <td>
            <a href="?edit=<?= urlencode($p['nis']) ?>">Edit</a>
            <form method="post" style="display:inline;">
                <input type="hidden" name="aksi" value="hapus_siswa">
                <input type="hidden" name="nis" value="<?= htmlspecialchars($p['nis']) ?>">
                <button type="submit">Hapus</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php TabelSekolah::renderTabelRelasi(); ?>

<h3>Data Nilai</h3>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>NIS</th>
        <th>Mapel</th>
        <th>Nilai</th>
        <th>Aksi</th>
    </tr>
    <?php if (empty($data['nilai'])): ?>
        <tr><td colspan="4">Kosong</td></tr>
    <?php else: ?>
        <?php foreach ($data['nilai'] as $i => $n): ?>
        <tr>
            <td><?= htmlspecialchars($n['nis']) ?></td>
            <td><?= htmlspecialchars($n['mapel']) ?></td>
            <td><?= (int)$n['nilai'] ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="aksi" value="hapus_nilai">
                    <input type="hidden" name="idx" value="<?= (int)$i ?>">
                    <button type="submit">Hapus</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

<br>
<?php unset($kumpulanObjek, $obj); ?>

</body>
</html>