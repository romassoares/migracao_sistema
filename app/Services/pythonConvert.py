

import sys
import os
import pandas as pd

ALLOWED_FORMATS = {"csv", "json"}

def normalize_path(path: str) -> str:
    path = path.strip().strip('"').strip("'")
    return os.path.abspath(os.path.normpath(path))


def is_valid_file(path: str) -> bool:
    return bool(path) and os.path.isfile(path)


def main():
    try:
        # -----------------------------
        # validar argumentos
        # -----------------------------
        if len(sys.argv) < 4:
            print("ERROR: Invalid arguments")
            print("Usage: python excel_converter.py <input_excel> <output_file> <format> [sheet]")
            sys.exit(1)

        input_excel = normalize_path(sys.argv[1])
        output_file = normalize_path(sys.argv[2])
        output_format = sys.argv[3].lower().strip()
        sheet_arg = sys.argv[4] if len(sys.argv) > 4 else 0

        # -----------------------------
        # validando arq
        # -----------------------------
        if not is_valid_file(input_excel):
            print(f"ERROR: Input file not found or invalid -> {input_excel}")
            sys.exit(2)

        if output_format not in ALLOWED_FORMATS:
            print("ERROR: Output format must be 'csv' or 'json'")
            sys.exit(3)

        # -----------------------------
        # criar instancia
        # -----------------------------
        try:
            if isinstance(sheet_arg, str) and sheet_arg.isdigit():
                sheet_arg = int(sheet_arg)
        except Exception:
            sheet_arg = 0

        # -----------------------------
        # le excel
        # -----------------------------
        excel = pd.ExcelFile(input_excel)
        available_sheets = excel.sheet_names

        if isinstance(sheet_arg, int):
            if sheet_arg < 0 or sheet_arg >= len(available_sheets):
                print("ERROR: Sheet index out of range")
                sys.exit(4)
            sheet = available_sheets[sheet_arg]
        else:
            if sheet_arg not in available_sheets:
                print(f"ERROR: Sheet '{sheet_arg}' not found")
                sys.exit(4)
            sheet = sheet_arg

        df = excel.parse(sheet)

        if df.empty:
            print("ERROR: Excel sheet is empty")
            sys.exit(5)

        output_dir = os.path.dirname(output_file)
        if output_dir and not os.path.isdir(output_dir):
            print(f"ERROR: Output directory does not exist -> {output_dir}")
            sys.exit(6)

        # -----------------------------
        # escreve saida
        # -----------------------------
        if output_format == "csv":
            df.to_csv(
                output_file,
                index=False,
                encoding="utf-8",
                lineterminator="\n"
            )
        else:  # json
            df.to_json(
                output_file,
                orient="records",
                force_ascii=False,
                date_format="iso"
            )

        print("SUCCESS")
        sys.exit(0)

    except pd.errors.EmptyDataError:
        print("ERROR: Excel file contains no data")
        sys.exit(7)

    except Exception as e:
        print(f"ERROR: Unexpected failure -> {str(e)}")
        sys.exit(99)


if __name__ == "__main__":
    main()
