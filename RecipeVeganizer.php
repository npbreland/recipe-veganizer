<?php


class RecipeVeganizer {
    
    /**
     * Dictionary of non-vegan items with their substitutes and ratios
     *
     * @var array
     */
    public static $subsDictionary = [
        'butter' => [
            'replaceStr' => 'vegan butter',
            'ratio' => 1
        ],
    
        'eggs' => [
            'replaceStr' => 'flax eggs',
            'ratio' => 1,
            'prep' => '1 Tbsp ground flaxseed + 2 1/2 Tbsp water. Stir together and allow 5 minutes to thicken.'
        ],
    
        'egg' => [
            'replaceStr' => 'flax egg',
            'ratio' => 1,
            'prep' => '1 Tbsp ground flaxseed + 2 1/2 Tbsp water. Stir together and allow 5 minutes to thicken.'
        ],
    
        'milk' => [
            'replaceStr' => 'non-dairy milk (soy, almond, oat, etc.)',
            'ratio' => 1
        ],
    
        'cream' => [
            'replaceStr' => 'coconut cream',
            'ratio' => 1
        ],
    
        'heavy cream' => [
            'replaceStr' => 'coconut cream',
            'ratio' => 1
        ],
    
        'honey' => [
            'replaceStr' => 'maple syrup/agave nectar',
            'ratio' => 1
        ],

        'ground beef' => [
            'replaceStr' => 'hydrated TVP',
            'ratio' => 1
        ],

        'ground turkey' => [
            'replaceStr' => 'hydrated TVP',
            'ratio' => 1
        ],

        'steak' => [
            'replaceStr' => 'tofu steak',
            'ratio' => 1
        ],

        'parmesan' => [
            'replaceStr' => 'vegan parmesan',
            'ratio' => 1
        ],

        'cream cheese' => [
            'replaceStr' => 'vegan cream cheese',
            'ratio' => 1
        ],

        'cheese' => [
            'replaceStr' => 'vegan cheese',
            'ratio' => 1
        ],

        'pulled pork' => [
            'replaceStr' => 'pulled jackfruit',
            'ratio' => 1
        ],

        'sausage' => [
            'replaceStr' => 'vegan sausage',
            'ratio' => 1
        ],

        
    ];

    /**
     * List of units to expect
     *
     * @var array
     */
    public static $units = [
        'ounces', 
        'oz.', 
        'fluid ounces',
        'fl oz',
        'fl. oz.',
        'fl.oz.',
    
        'teaspoons',
        'teaspoon',
        'tsp',
        'tablespoons',
        'tablespoon',
        'Tbsp',
        'T',
        
        'ml',
        'milliliter',
        'milliliters',
        'L', 
        'liter',
        'liters',
        'cup',
        'cups', 
        'C',
        'pint',
        'pt.',
        'pt',
        'quart',
        'qt.',
        'qt',
        'gallon',
        'gallons',
    
        'stick',
        'sticks',
        'g', 
        'gram',
        'grams', 
    ];

    protected $recipeLines;

    /**
     * This method rounds a decimal result to the closest "friendly fraction,"
     * that is, one that is commonly found in recipes. For example, I've never seen
     * a 0.568 cup measuring cup; this method would round that to 1/2 cup, which
     * is probably close enough for most recipes.
     *
     * @param float $decimal
     * @return mixed
     */
    public function roundToCommonFractions(float $decimal)
    {
        if ($decimal < 0.125) {
            return 0;
        }

        if ($decimal >= 0.125 && $decimal < 0.29) {
            $fraction = '1/4';
        } else if ($decimal < 0.415) {
            $fraction = '1/3';
        } else if ($decimal < 0.58) {
            $fraction = '1/2';
        } else if ($decimal < 0.705) {
            $fraction = '2/3';
        } else if ($decimal < 0.875) {
            $fraction = '3/4';
        } else {
            $fraction = 1;
        }

        return $fraction;
    }

    /**
     * The main method of this class. Takes a recipe's ingredients list... 
     * and veganizes it! The method splits the recipe into an array of recipe 
     * lines, each of which contains a quantity, unit, and item element.
     * 
     * See RecipeVeganizer::getHtml() or RecipeVeganizer::getJson() for output.
     * 
     * IMPORTANT: Expects the ingredients list to be in the form commonly found 
     * in recipes, like this:
     * 
     * 3 cups cake flour
     * 1 tablespoon baking powder
     * 3/4 teaspoon salt
     * 2 sticks unsalted butter
     * 1 1/2 cups granulated sugar
     * 1 1/2 teaspoons vanilla extract
     * 3 large eggs
     * 1 1/4 cups whole milk 
     *
     * @param string $recipeToVeganize
     * @return void
     */
    public function veganize(string $recipeToVeganize)
    {
        /* The following methods have been created to make this main method 
        easier to read. They must be carried out in the order they are given 
        here, as each method depends on the last. */
        
        $this->splitInputIntoLines($recipeToVeganize);
        $this->splitLinesIntoQtyAndItemPieces();
        $this->separateUnitFromItemString();        
        $this->substituteAndAdjustQuantities();
        $this->convertDecimalsToFractions();
    }

    /**
     * Splits the input recipe into lines.
     *
     * @param string $recipeToVeganize
     * @return void
     */
    private function splitInputIntoLines(string $recipeToVeganize)
    {
        $this->recipeLines = preg_split('/(\n|\r)/', $recipeToVeganize, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Splits the recipe lines into an array of two elements: quantity and item.
     *
     * @return void
     */
    private function splitLinesIntoQtyAndItemPieces()
    {
        // Split lines into qty and item pieces
        $this->recipeLines = array_map(function($line) {
            
            $pieces = preg_split('/^(\d+\/\d+|\d+(\s\d+\/\d+)?)/', $line, -1, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_NO_EMPTY);

            // Only get the first match
            $str = trim($pieces[0][0]);
            $offset = $pieces[0][1];

            $qty = substr($line, 0, $offset);
            
            return [ 'qty' => $qty, 'item' => $str ]; 
        }, $this->recipeLines);
    }

    /**
     * Uses the RecipeVeganizer::$units dictionary to find the unit in the item
     * string for each line in the recipe, splits the item string into 'unit' and
     * 'item' pieces, and then replaces the existing 'item' piece with the two 
     * new pieces.
     *
     * @return void
     */
    private function separateUnitFromItemString()
    {
        $this->recipeLines = array_map(function($line){
            $itemStr = $line['item'];
            foreach (self::$units as $unit) {
                
                $pieces = preg_split("/^($unit)\s+/", $itemStr, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

                if ($pieces[1]) {
                    return [
                        'qty' => $line['qty'],
                        'unit' => trim($pieces[0]),
                        'item' => trim($pieces[1])
                    ];
                }
                
            }

            $line['unit'] = '';
            return $line;
            
        }, $this->recipeLines);
    }

    /**
     * For each line in the recipe, lookup the item in 
     * RecipeVeganizer::$subsDictionary, and replace the item with the substitute
     * if one is found. At the same time, adjust the quantity based on the ratio
     * provided in the dictionary.
     *
     * @return void
     */
    private function substituteAndAdjustQuantities()
    {
        $this->recipeLines = array_map(function($line){

            $itemStr = $line['item'];
            $qty = $line['qty'];

            foreach (self::$subsDictionary as $toSub => $valArr) {
                $subTo = $valArr['replaceStr'];
                $matchFound = preg_match("/$toSub.*/", $itemStr);
                if ($matchFound) {
                    // We replaced something. 
                    $ratio = $valArr['ratio'];
                    $newQty = $qty * $ratio;

                    return [
                        'qty' => $newQty,
                        'unit' => $line['unit'],
                        'item' => $subTo,
                        'prep' => $valArr['prep']
                    ];
                }
            }

            // We didn't find a match - so just return it unchanged.
            return $line;

        }, $this->recipeLines);
    }

    /**
     * Converts decimals in the recipe quantities to common fractions used in
     * baking.
     *
     * @return void
     */
    private function convertDecimalsToFractions()
    {
        // Convert decimals back to fractions
        $this->recipeLines = array_map(function($line) {

            $qty = $line['qty'];
            
            if (!$qty) {
                // No qty value, so skip
                return $line;
            }

            $qtyWhole = floor($qty);
            $qtyDecimal = floatval($qty - $qtyWhole);

            // Convert to fraction notation.
            $fraction = $this->roundToCommonFractions($qtyDecimal);

            if (!$qtyWhole) {
                // If no whole number to add it to, just use the fraction
                $line['qty'] = $fraction;
                return $line;
            }

            if (gettype($fraction) === 'string') {
                // It's a fraction, so concat it to the whole number
                $qty = $qtyWhole . ' ' . $fraction;
            } else {
                // It's an int, so add it to the whole number.
                $qty = $qtyWhole + $fraction;
            }

            $line['qty'] = $qty;
            return $line;

        }, $this->recipeLines);
    }

    /**
     * Returns an HTML string of the recipe.
     *
     * @return string
     */
    public function getHtml(): string
    {
        // Build each line
        $this->recipeLines = array_map(function($line){
            return implode(' ', [ $line['qty'], $line['unit'], $line['item'] ]);
        }, $this->recipeLines);

        // Add html line break characters.
        $response = implode('&#13;&#10;', $this->recipeLines);
        return $response;
    }

    /**
     * Returns the recipe lines encoded in JSON.
     *
     * @return string|false
     */
    public function getJson()
    {
        return json_encode($this->recipeLines);
    }
}


