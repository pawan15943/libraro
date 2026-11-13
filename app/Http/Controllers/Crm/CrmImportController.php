<?php

namespace App\Http\Controllers\Crm;

use App\Models\Crm\CrmImport;
use Illuminate\Http\Request;

/** Upload leads from a CSV file (Excel → Save as CSV) or a pasted list. */
class CrmImportController extends CrmBaseController
{
    /** CSV header → lead field. Several spellings are accepted. */
    private const COLUMNS = [
        'mobile' => ['mobile', 'phone', 'mobile number', 'phone number', 'contact', 'contact number', 'number', 'whatsapp'],
        'library_name' => ['library', 'library name', 'business', 'company', 'study centre', 'study center'],
        'contact_name' => ['name', 'contact name', 'owner', 'owner name', 'person'],
        'email' => ['email', 'email id', 'mail'],
        'city' => ['city', 'town', 'district'],
        'state' => ['state'],
        'notes' => ['notes', 'note', 'remarks', 'comment', 'comments'],
        'priority' => ['priority'],
    ];

    public function create()
    {
        return view('crm.import', ['imports' => CrmImport::with(['user', 'agent'])->latest()->limit(15)->get()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'method' => ['required', 'in:csv,paste'],
            'file' => ['required_if:method,csv', 'nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'rows' => ['required_if:method,paste', 'nullable', 'string', 'max:200000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ], ['file.mimes' => 'Upload a .csv file (in Excel: File → Save as → CSV).']);

        $rows = $request->input('method') === 'csv'
            ? $this->readCsv($request->file('file')->getRealPath())
            : $this->readPaste((string) $request->input('rows'));

        if (! $rows) {
            return back()->withInput()->withErrors(['file' => 'No rows found. The file needs a "mobile" column.']);
        }
        if (count($rows) > 5000) {
            return back()->withInput()->withErrors(['file' => 'Upload at most 5,000 leads at a time.']);
        }

        $r = $this->leads->import($rows, $this->user(), $request->integer('assigned_to') ?: null, $request->input('method'),
            $request->file('file')?->getClientOriginalName());

        return redirect()->route('crm.import')->with('success', "{$r['created']} leads added · {$r['duplicates']} already in the CRM · {$r['invalid']} without a valid mobile number.");
    }

    public function template()
    {
        $csv = "library_name,contact_name,mobile,email,city,state,priority,notes\n"
            ."Gyan Deep Library,Rakesh Sharma,9876543210,rakesh@example.com,Jaipur,Rajasthan,hot,Met at expo\n";

        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="libraro-leads-template.csv"']);
    }

    private function readCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        $first = fgets($fh);
        if ($first === false) {
            return [];
        }
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first); // Excel BOM
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $header = array_map(fn ($h) => strtolower(trim($h)), str_getcsv($first, $delimiter));
        $map = $this->mapHeader($header);

        $rows = [];
        if (! isset($map['mobile'])) {
            // no header row: treat the first line as data with mobile in any column
            rewind($fh);
            $map = null;
        }
        while (($cells = fgetcsv($fh, 0, $delimiter)) !== false) {
            if (! array_filter($cells, fn ($c) => trim((string) $c) !== '')) {
                continue;
            }
            $rows[] = $map ? $this->rowFromMap($cells, $map) : $this->guessRow($cells);
        }
        fclose($fh);

        return $rows;
    }

    /** One lead per line: "Library name, contact name, mobile, city" in any order. */
    private function readPaste(string $text): array
    {
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = preg_split('/\t|,|;|\|/', $line);
            $rows[] = $this->guessRow($cells);
        }

        return $rows;
    }

    private function mapHeader(array $header): array
    {
        $map = [];
        foreach (self::COLUMNS as $field => $names) {
            foreach ($header as $i => $h) {
                if (in_array(str_replace(['_', '-'], ' ', $h), $names, true) || $h === $field) {
                    $map[$field] = $i;
                    break;
                }
            }
        }

        return $map;
    }

    private function rowFromMap(array $cells, array $map): array
    {
        $row = [];
        foreach ($map as $field => $i) {
            $row[$field] = isset($cells[$i]) ? trim((string) $cells[$i]) : null;
        }

        return $row;
    }

    /**
     * Without a header: the cell with 10+ digits is the mobile, an e-mail is the e-mail.
     * Text before the mobile is library, then contact name; text after it is city, then state.
     */
    private function guessRow(array $cells): array
    {
        $row = [];
        $before = $after = [];
        foreach ($cells as $c) {
            $c = trim((string) $c);
            if ($c === '') {
                continue;
            }
            if (! isset($row['mobile']) && strlen(preg_replace('/\D/', '', $c)) >= 10) {
                $row['mobile'] = $c;
            } elseif (! isset($row['email']) && filter_var($c, FILTER_VALIDATE_EMAIL)) {
                $row['email'] = $c;
            } elseif (isset($row['mobile'])) {
                $after[] = $c;
            } else {
                $before[] = $c;
            }
        }
        // "9876543210, Gyan Deep Library, Kota": nothing before the number → library comes first after it
        if (! $before && $after) {
            $before[] = array_shift($after);
        }
        $row['library_name'] = $before[0] ?? null;
        $row['contact_name'] = $before[1] ?? null;
        $row['city'] = $after[0] ?? ($before[2] ?? null);
        $row['state'] = $after[1] ?? null;

        return $row;
    }
}
