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
     * The main function of this class. Takes a recipe's ingredients list... 
     * and veganizes it!
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
     * Output is in HTML.
     *
     * @param string $recipeToVeganize
     * @return string
     */
    public function veganize(string $recipeToVeganize)
    {
        // Split input string into lines
        $recipeLines = preg_split('/(\n|\r)/', $recipeToVeganize, -1, PREG_SPLIT_NO_EMPTY);

        // Split lines into qty and item pieces
        $recipeLines = array_map(function($line) {
            
            $pieces = preg_split('/^(\d+\/\d+|\d+(\s\d+\/\d+)?)/', $line, -1, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_NO_EMPTY);

            // Only get the first match
            $str = trim($pieces[0][0]);
            $offset = $pieces[0][1];

            $qty = substr($line, 0, $offset);
            
            return [ 'qty' => $qty, 'item' => $str ]; 
        }, $recipeLines);


        // Convert the mixed numbers to decimals so we can do math.
        $recipeLines = array_map(function($line) {

            $qty = $line['qty'];

            preg_match('/\d+\/\d+/', $qty, $matches, PREG_OFFSET_CAPTURE);

            // If no matches, continue on.
            if (count($matches) === 0) {
                return $line;
            }

            // Only get the first match
            $match = $matches[0][0];
            $offset = $matches[0][1];

            $decimal = 0;
            // The fraction is offset, which means we have a whole number
            if ($offset > 0) {
                $decimal = intval(substr($qty, 0, $offset));
            }

            $fractionPieces = explode('/', $match);
            $numer = $fractionPieces[0]; 
            $denom = $fractionPieces[1];

            $decimal += $numer / $denom;

            $line['qty'] = $decimal;

            return $line;

        }, $recipeLines);

        // Further split item string into unit and item pieces
        $recipeLines = array_map(function($line){
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
            
        }, $recipeLines);

        // Do the replacement and adjust the quantities
        $recipeLines = array_map(function($line){

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

        }, $recipeLines);

        // Convert decimals back to fractions
        $recipeLines = array_map(function($line) {

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

        }, $recipeLines);

        // Finally, let's build the response HTML
        $recipeLines = array_map(function($line){
            return implode(' ', [ $line['qty'], $line['unit'], $line['item'] ]);
        }, $recipeLines);

        // Add html line break characters.
        $responseText = implode('&#13;&#10;', $recipeLines);

        return $responseText;
    }
}


